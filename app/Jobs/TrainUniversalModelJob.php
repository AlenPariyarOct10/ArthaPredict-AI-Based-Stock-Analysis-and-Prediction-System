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

class TrainUniversalModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $modelType; // 'lstm', 'xgboost', or 'random_forest'
    public $userId;
    public $trainingJobId;
    public $timeout = 600; // 10 minutes for universal model training
    public $tries = 3;
    public $backoff = [60, 120, 240];

    private const LOCK_PREFIX = 'universal_model_training_lock_';
    private const LOCK_TTL = 600;

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
            $scriptPath = base_path("ml_service/{$this->modelType}_universal_model.py");

            $command = sprintf(
                '%s %s --train 2>&1',
                escapeshellcmd($pythonExecutable),
                escapeshellarg($scriptPath)
            );

            Log::info("Executing Universal Model Training", [
                'command' => $command,
                'model_type' => $this->modelType
            ]);

            $output = shell_exec($command);

            Log::debug("Universal Model Training Raw Output", [
                'model_type' => $this->modelType,
                'output' => $output,
            ]);

            // Mark job as completed and set processed_rows to total_rows (100% progress)
            $trainingJob->update([
                'status' => 'completed',
                'completed_at' => now(),
                'current_stage' => 'completed',
                'processed_rows' => $trainingJob->total_rows,
                'meta' => [
                    'model_type' => $this->modelType,
                    'output' => $output
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
}
