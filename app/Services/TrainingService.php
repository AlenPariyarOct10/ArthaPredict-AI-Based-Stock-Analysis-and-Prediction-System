<?php

namespace App\Services;

use App\Models\Stock;
use Illuminate\Support\Facades\Log;

class TrainingService
{
    protected $modelRegistry;

    public function __construct(ModelRegistryService $modelRegistry)
    {
        $this->modelRegistry = $modelRegistry;
    }

    public function trainModel(Stock $stock, ?int $jobId = null, bool $forceRetrain = false): ?array
    {
        $pythonExecutable = config('services.python.executable', env('PYTHON_EXECUTABLE', 'python'));
        $scriptPath = base_path('ml_service/predict.py');

        // Build args: symbol, optional job_id, then flags
        $args = [
            escapeshellcmd($pythonExecutable),
            escapeshellarg($scriptPath),
            escapeshellarg($stock->symbol),
        ];

        if ($jobId) {
            $args[] = escapeshellarg((string) $jobId);
        }

        $args[] = '--train-only';

        if ($forceRetrain) {
            $args[] = '--force-retrain';
        }

        $args[] = '2>&1';

        $command = implode(' ', $args);

        Log::info("Executing Python Training", ['command' => $command, 'symbol' => $stock->symbol]);

        $output = shell_exec($command);

        Log::debug("Python Training Raw Output", [
            'symbol' => $stock->symbol,
            'output' => $output,
        ]);

        if (!$output) {
            throw new \Exception("Python script produced no output for {$stock->symbol}. Check Laravel logs for the command.");
        }

        $jsonStart = strpos($output, '{');
        $jsonEnd = strrpos($output, '}');
        $jsonString = null;

        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd >= $jsonStart) {
            $jsonString = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
        }

        $result = $jsonString ? json_decode($jsonString, true) : null;

        if (json_last_error() !== JSON_ERROR_NONE || !$result || isset($result['error'])) {
            $errorMessage = ($result['error'] ?? null) ?: trim($output) ?: 'Unknown error parsing script output.';
            Log::error("Training failed", ['symbol' => $stock->symbol, 'raw_output' => $output]);
            throw new \Exception("Training error: " . $errorMessage);
        }

        if (isset($result['models']) && is_array($result['models'])) {
            foreach ($result['models'] as $modelInfo) {
                $this->modelRegistry->registerModel($stock, [
                    'model_type' => $modelInfo['model_type'],
                    'metrics' => $modelInfo['metrics'] ?? [],
                    'data_length' => $modelInfo['data_length'] ?? null,
                    'training_date' => now(),
                ]);
            }
        }

        return $result;
    }
}
