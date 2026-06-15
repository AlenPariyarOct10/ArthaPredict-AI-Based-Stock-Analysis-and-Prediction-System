<?php

namespace App\Jobs;

use App\Models\ModelTrainingJob;
use App\Models\Stock;
use App\Services\ModelRegistryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class TrainIndividualModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 1;

    public function __construct(
        public int $stockId,
        public string $modelType,
        public int $userId,
        public int $trainingJobId
    ) {
    }

    public function handle(ModelRegistryService $registry): void
    {
        $stock = Stock::findOrFail($this->stockId);
        $trainingJob = ModelTrainingJob::findOrFail($this->trainingJobId);
        $trainingJob->update([
            'status' => 'processing',
            'started_at' => now(),
            'current_stage' => "training {$stock->symbol}",
        ]);

        try {
            $process = new Process([
                config('services.python.executable', env('PYTHON_EXECUTABLE', 'python')),
                base_path('ml_service/universal_model.py'),
                '--train',
                '--algorithm',
                $this->modelType,
                '--scope',
                'individual',
                '--symbol',
                $stock->symbol,
            ], base_path());
            $process->setTimeout(3500);
            $process->run();

            $output = trim($process->getOutput());
            $result = $this->decodeLastJsonLine($output);
            if (!$process->isSuccessful()) {
                $message = $result['error']
                    ?? (trim($process->getErrorOutput()) ?: $output);
                throw new \RuntimeException("Individual training failed: {$message}");
            }
            if (!$result || ($result['status'] ?? null) !== 'ok') {
                throw new \RuntimeException('Individual training returned an invalid response.');
            }

            $metadataPath = base_path(
                "ml_service/models/individual/{$stock->symbol}/metadata.json"
            );
            $metadata = is_file($metadataPath)
                ? json_decode(file_get_contents($metadataPath), true)
                : [];

            foreach ($result['models'] ?? [] as $model) {
                $modelMetadata = $metadata[$model['algorithm']] ?? [];
                $registry->registerModel($stock, [
                    'model_type' => $model['algorithm'],
                    'path' => $modelMetadata['model_path'] ?? $model['path'] ?? null,
                    'latest_path' => $modelMetadata['latest_path'] ?? null,
                    'metrics' => $model['metrics'] ?? [],
                    'training_date' => $modelMetadata['training_date'] ?? now(),
                    'data_length' => $stock->prices()->count(),
                    'config' => array_merge(
                        $modelMetadata['config'] ?? [],
                        [
                            'model_scope' => 'individual',
                            'stock_symbol' => $stock->symbol,
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
                'processed_rows' => 1,
                'meta' => array_merge($trainingJob->meta ?? [], [
                    'models' => $result['models'] ?? [],
                ]),
            ]);
        } catch (\Throwable $exception) {
            $trainingJob->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);
            Log::error('Individual model training failed', [
                'stock_id' => $stock->id,
                'model_type' => $this->modelType,
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
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
