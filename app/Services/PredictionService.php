<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockPrediction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PredictionService
{
    protected $modelRegistry;

    public function __construct(ModelRegistryService $modelRegistry)
    {
        $this->modelRegistry = $modelRegistry;
    }

    public function generatePredictions(Stock $stock): ?array
    {
        $pythonExecutable = config('services.python.executable', env('PYTHON_EXECUTABLE', 'python'));
        $scriptPath = base_path('ml_service/predict.py');

        // Note: We use --predict-only to avoid training and use cached models
        $command = sprintf(
            '%s %s %s --predict-only 2>&1',
            escapeshellcmd($pythonExecutable),
            escapeshellarg($scriptPath),
            escapeshellarg($stock->symbol)
        );

        Log::info("Executing Python Prediction", ['command' => $command, 'symbol' => $stock->symbol]);

        $output = shell_exec($command);

        if (!$output) {
            throw new \Exception("Failed to execute prediction script for {$stock->symbol}");
        }

        $jsonStart = strpos($output, '{');
        $jsonEnd = strrpos($output, '}');
        $jsonString = null;

        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd >= $jsonStart) {
            $jsonString = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
        }

        $result = $jsonString ? json_decode($jsonString, true) : null;

        if (json_last_error() !== JSON_ERROR_NONE || !$result || isset($result['error'])) {
            $errorMessage = $result['error'] ?? trim($output) ?: 'Unknown error parsing script output.';
            throw new \Exception("Prediction error: " . $errorMessage);
        }

        if (!isset($result['predictions']) || !is_array($result['predictions']) || empty($result['predictions'])) {
            throw new \Exception("No predictions were returned from the model.");
        }

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

        return $result['predictions'];
    }
}
