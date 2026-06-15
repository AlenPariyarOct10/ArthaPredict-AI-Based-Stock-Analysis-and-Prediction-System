<?php

namespace App\Jobs;

use App\Models\ModelTrainingJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ModelRegistryService;
use Symfony\Component\Process\Process;

class TrainUniversalModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $modelType; // 'lstm', 'xgboost', or 'random_forest'
    public $userId;
    public $trainingJobId;
    public $timeout = 3600;
    public $tries = 1;
    public $backoff = [60, 120, 240];

    private const LOCK_PREFIX = 'universal_model_training_lock_';
    private const LOCK_TTL = 3600;

    public function __construct(string $modelType, $userId, $trainingJobId)
    {
        $this->modelType = $modelType;
        $this->userId = $userId;
        $this->trainingJobId = $trainingJobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lockKey = self::LOCK_PREFIX . $this->modelType;
        $lock = Cache::lock($lockKey, self::LOCK_TTL);

        if (!$lock->get()) {
            Log::warning("Universal model training already in progress for type: {$this->modelType}");
            $this->release(60);
            return;
        }

        try {
            $trainingJob = ModelTrainingJob::find($this->trainingJobId);
            if (!$trainingJob) {
                return;
            }

            $trainingJob->update([
                'status' => 'processing',
                'started_at' => now(),
                'current_stage' => 'initializing'
            ]);

            $pythonExecutable = config('services.python.executable', env('PYTHON_EXECUTABLE', 'python'));
            $scriptPath = base_path('ml_service/universal_model.py');
            $process = new Process([
                $pythonExecutable,
                $scriptPath,
                '--train',
                '--algorithm',
                $this->modelType,
            ], base_path());
            $process->setTimeout(3500);

            Log::info("Executing Universal Model Training", [
                'model_type' => $this->modelType
            ]);

            $process->run();
            $output = trim($process->getOutput());
            $result = $this->decodeLastJsonLine($output);

            Log::debug("Universal Model Training Raw Output", [
                'model_type' => $this->modelType,
                'output' => $output,
            ]);

            if (!$process->isSuccessful()) {
                $message = $result['error']
                    ?? (trim($process->getErrorOutput()) ?: $output);
                throw new \RuntimeException("Universal training failed: {$message}");
            }

            if (!$result || ($result['status'] ?? null) !== 'ok') {
                throw new \RuntimeException('Universal training returned an invalid response.');
            }

            $registry = app(ModelRegistryService::class);
            $metadataPath = base_path('ml_service/models/universal/metadata.json');
            $metadata = is_file($metadataPath)
                ? json_decode(file_get_contents($metadataPath), true)
                : [];
            foreach ($result['models'] ?? [] as $model) {
                $modelMetadata = $metadata[$model['algorithm']] ?? [];
                $registry->registerUniversalModel([
                    'model_type' => $model['algorithm'],
                    'path' => $modelMetadata['model_path'] ?? $model['path'] ?? null,
                    'latest_path' => $modelMetadata['latest_path'] ?? null,
                    'metrics' => $model['metrics'] ?? [],
                    'training_date' => $modelMetadata['training_date'] ?? now(),
                    'data_length' => $modelMetadata['stock_count'] ?? null,
                    'config' => array_merge(
                        $modelMetadata['config'] ?? [],
                        [
                            'symbol_to_index' => $modelMetadata['symbol_to_index'] ?? [],
                            'benchmark' => $modelMetadata['extra']['benchmark'] ?? false,
                        ]
                    ),
                ]);
            }

            $trainingJob->update([
                'status' => 'completed',
                'completed_at' => now(),
                'current_stage' => 'completed',
                'processed_rows' => $trainingJob->total_rows,
                'meta' => [
                    'model_type' => $this->modelType,
                    'models' => $result['models'] ?? [],
                ]
            ]);

            Log::info("Universal model training completed", ['model_type' => $this->modelType]);

        } catch (\Throwable $e) {
            Log::error('Universal model training job failed', [
                'model_type' => $this->modelType,
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

            throw $e;
        } finally {
            $lock->release();
        }
    }

    private function decodeLastJsonLine(string $output): ?array
    {
        $lines = array_reverse(preg_split('/\r\n|\r|\n/', $output) ?: []);
        foreach ($lines as $line) {
            $decoded = json_decode(trim($line), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        return null;
    }
}
