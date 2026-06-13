<?php

namespace App\Jobs;

use App\Models\Stock;
use App\Models\ModelTrainingJob;
use App\Models\StockPrediction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\TrainingService;

class TrainModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $stockId;
    public $userId;
    public $trainingJobId;
    public $timeout = 300; // 5 minutes per stock
    public $tries = 3; // Retry 3 times on failure
    public $backoff = [30, 60, 120]; // Exponential backoff

    // Prevent concurrent jobs for same stock
    private const LOCK_PREFIX = 'model_training_lock_';
    private const LOCK_TTL = 300; // Lock for 300 seconds

    public function __construct($stockId, $userId, $trainingJobId)
    {
        $this->stockId = $stockId;
        $this->userId = $userId;
        $this->trainingJobId = $trainingJobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lockKey = self::LOCK_PREFIX . $this->stockId;
        $lock = Cache::lock($lockKey, self::LOCK_TTL);

        if (!$lock->get()) {
            Log::warning("Model training already in progress for stock ID: {$this->stockId}");
            $this->release(30); // Try again in 30 seconds
            return;
        }

        try {
            $trainingJob = ModelTrainingJob::find($this->trainingJobId);
            if (!$trainingJob) {
                return;
            }

            $trainingJob->update(['status' => 'processing', 'started_at' => now()]);

            $stock = Stock::findOrFail($this->stockId);
            $symbol = $stock->symbol;

            // Check if we need to retrain or can use existing
            $needsTraining = $this->checkIfTrainingNeeded($stock);

            if (!$needsTraining) {
                $trainingJob->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'meta' => ['cached' => true]
                ]);
                Log::info("Using cached model for stock: {$symbol}");
                $lock->release();
                return;
            }

            // Train with progress tracking
            /** @var TrainingService $trainingService */
            $trainingService = app(TrainingService::class);

            $forceRetrain = $trainingJob->meta['force_retrain'] ?? false;
            $result = $trainingService->trainModel($stock, $trainingJob->id, $forceRetrain);

            // Cache the model metadata
            $this->cacheModelMetadata($stock, $result);

            $trainingJob->update([
                'status' => 'completed',
                'completed_at' => now(),
                'meta' => [
                    'cached' => false,
                    'training_time' => $result['training_time'] ?? null,
                    'metrics' => $result['models'][0]['metrics'] ?? []
                ]
            ]);

            Log::info("Model training completed for stock: {$symbol}");

        } catch (\Throwable $e) {
            Log::error('Model training job failed', [
                'stock_id' => $this->stockId,
                'training_job_id' => $this->trainingJobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $trainingJob = ModelTrainingJob::find($this->trainingJobId);
            if ($trainingJob) {
                $trainingJob->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            }

            // Re-throw to trigger retry
            throw $e;
        } finally {
            $lock->release();
        }
    }

    /**
     * Check if retraining is necessary
     */
    private function checkIfTrainingNeeded(Stock $stock): bool
    {
        // Check if predictions exist
        $latestPrediction = StockPrediction::where('stock_id', $stock->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$latestPrediction) {
            return true; // No predictions exist, need training
        }

        // Check age of predictions (retrain weekly)
        $daysSinceTraining = now()->diffInDays($latestPrediction->created_at);
        if ($daysSinceTraining >= 7) {
            Log::info("Model for stock {$stock->symbol} is {$daysSinceTraining} days old, retraining");
            return true;
        }

        // Check if new data is available (implement based on your data update frequency)
        $hasNewData = $this->checkForNewData($stock);
        if ($hasNewData) {
            Log::info("New data available for stock {$stock->symbol}, retraining");
            return true;
        }

        return false;
    }

    /**
     * Check if new price data exists
     */
    private function checkForNewData(Stock $stock): bool
    {
        // Get last training date from metadata
        $lastTrainingMeta = DB::table('stock_predictions')
            ->where('stock_id', $stock->id)
            ->orderBy('created_at', 'desc')
            ->value('additional_metrics');

        if (!$lastTrainingMeta) {
            return true;
        }

        $lastTrainingMeta = is_string($lastTrainingMeta)
            ? json_decode($lastTrainingMeta, true)
            : $lastTrainingMeta;

        $lastDataPoint = $lastTrainingMeta['last_data_date'] ?? null;

        if (!$lastDataPoint) {
            return true;
        }

        // Check if we have new price data after last training
        $newDataCount = DB::table('stock_prices')
            ->where('stock_id', $stock->id)
            ->where('date', '>', $lastDataPoint)
            ->count();

        return $newDataCount > 0;
    }

    // Removed executePythonTraining, parsePythonOutput, savePredictions as they are handled by TrainingService

    /**
     * Cache model metadata for quick lookup
     */
    private function cacheModelMetadata(Stock $stock, array $result): void
    {
        $cacheKey = "stock_model_metadata_{$stock->id}";

        Cache::put($cacheKey, [
            'last_trained' => now()->toISOString(),
            'metrics' => $result['metrics'] ?? [],
            'data_points' => $result['data_points'] ?? null,
            'symbol' => $stock->symbol,
        ], now()->addDays(7));
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('TrainModelJob failed permanently', [
            'stock_id' => $this->stockId,
            'training_job_id' => $this->trainingJobId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        $trainingJob = ModelTrainingJob::find($this->trainingJobId);
        if ($trainingJob) {
            $trainingJob->update([
                'status' => 'failed',
                'error_message' => "Failed after {$this->attempts()} attempts: " . $exception->getMessage(),
                'completed_at' => now(),
            ]);
        }

        // Notify user (implement notification system)
        // Notification::send($this->user, new ModelTrainingFailed($stock, $exception));
    }
}
