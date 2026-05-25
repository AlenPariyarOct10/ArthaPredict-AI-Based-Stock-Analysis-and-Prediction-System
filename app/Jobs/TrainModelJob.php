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
use Illuminate\Support\Facades\DB;

class TrainModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $stockId;
    public $userId;
    public $trainingJobId;

    // Generous execution timeout of 5 minutes (300 seconds) for heavy calculations
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
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
        $trainingJob = ModelTrainingJob::find($this->trainingJobId);
        if (!$trainingJob) {
            return;
        }

        $trainingJob->update(['status' => 'processing']);

        try {
            $stock = Stock::findOrFail($this->stockId);
            $symbol = $stock->symbol;

            $pythonExecutable = config('services.python.executable', env('PYTHON_EXECUTABLE', 'python'));
            $scriptPath = base_path('ml_service/predict.py');
            
            $pythonCommand = sprintf(
                '%s %s %s 2>&1',
                escapeshellcmd($pythonExecutable),
                escapeshellarg($scriptPath),
                escapeshellarg($symbol)
            );

            // Execute Python command
            $output = shell_exec($pythonCommand);

            if (!$output) {
                throw new \Exception('Failed to execute prediction script. Verify that Python and required packages are installed.');
            }

            // Extract the JSON substring robustly to ignore leading/trailing debugging or warning outputs
            $jsonStart = strpos($output, '{');
            $jsonEnd = strrpos($output, '}');
            $jsonString = null;

            if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd >= $jsonStart) {
                $jsonString = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
            }

            $result = $jsonString ? json_decode($jsonString, true) : null;

            if (json_last_error() !== JSON_ERROR_NONE || !$result || isset($result['error'])) {
                $errorMessage = $result['error'] ?? trim($output) ?: 'Unknown error parsing script output.';
                throw new \Exception($errorMessage);
            }

            if (!isset($result['predictions']) || !is_array($result['predictions']) || empty($result['predictions'])) {
                throw new \Exception('Training completed but no predictions were returned.');
            }

            // Save predictions inside a database transaction
            DB::transaction(function () use ($stock, $result) {
                StockPrediction::where('stock_id', $stock->id)->delete();

                foreach ($result['predictions'] as $prediction) {
                    StockPrediction::create([
                        'stock_id' => $stock->id,
                        'model_type' => $prediction['model_type'],
                        'target_date' => $prediction['target_date'],
                        'predicted_price' => $prediction['predicted_price'],
                        'additional_metrics' => is_array($prediction['additional_metrics'] ?? null)
                            ? $prediction['additional_metrics']
                            : ['raw' => (string) ($prediction['additional_metrics'] ?? '')],
                    ]);
                }
            });

            $trainingJob->update([
                'status' => 'completed',
            ]);

        } catch (\Throwable $e) {
            Log::error('Model training job failed', [
                'stock_id' => $this->stockId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $trainingJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
