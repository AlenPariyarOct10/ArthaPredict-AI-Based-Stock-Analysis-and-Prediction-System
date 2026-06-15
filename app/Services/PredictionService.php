<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockPrediction;
use App\Models\StockPrice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

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
        $scriptPath = base_path('ml_service/universal_model.py');
        $process = new Process([
            $pythonExecutable,
            $scriptPath,
            '--predict-stdin',
            $stock->symbol,
            '--algorithm',
            'all',
            '--scope',
            'both',
        ], base_path());
        $process->setTimeout(300);
        $history = StockPrice::where('stock_id', $stock->id)
            ->orderBy('date')
            ->get(['date', 'close']);
        $process->setInput(json_encode([
            'dates' => $history->pluck('date')->map(
                fn ($date) => \Carbon\Carbon::parse($date)->format('Y-m-d')
            )->values(),
            'prices' => $history->pluck('close')->map(
                fn ($price) => (float) $price
            )->values(),
        ], JSON_THROW_ON_ERROR));

        Log::info('Executing scoped Python prediction', ['symbol' => $stock->symbol]);
        $process->run();
        $output = trim($process->getOutput());
        $result = $this->decodeLastJsonLine($output);

        if (!$process->isSuccessful()) {
            $message = $result['error']
                ?? (trim($process->getErrorOutput()) ?: $output);
            throw new \Exception("Prediction error: {$message}");
        }

        if (!$result || ($result['status'] ?? null) !== 'ok') {
            throw new \Exception('Prediction error: invalid scoped model response.');
        }

        if (!isset($result['predictions']) || !is_array($result['predictions']) || empty($result['predictions'])) {
            throw new \Exception("No predictions were returned from the model.");
        }

        DB::transaction(function () use ($stock, $result) {
            foreach ($result['predictions'] as $prediction) {
                StockPrediction::updateOrCreate(
                    [
                        'stock_id' => $stock->id,
                        'model_type' => $prediction['model_type'],
                        'model_scope' => $prediction['model_scope'] ?? 'universal',
                        'target_date' => $prediction['target_date'],
                    ],
                    [
                        'predicted_price' => $prediction['predicted_price'],
                        'additional_metrics' => is_array($prediction['additional_metrics'] ?? null)
                            ? $prediction['additional_metrics']
                            : ['raw' => (string) ($prediction['additional_metrics'] ?? '')],
                    ]
                );
            }
        });

        return $result['predictions'];
    }

    public function generatePredictionsForAll(): array
    {
        $pythonExecutable = config('services.python.executable', env('PYTHON_EXECUTABLE', 'python'));
        $process = new Process([
            $pythonExecutable,
            base_path('ml_service/universal_model.py'),
            '--predict-all',
            '--algorithm',
            'all',
            '--scope',
            'both',
        ], base_path());
        $process->setTimeout(3600);
        $process->run();

        $output = trim($process->getOutput());
        $result = $this->decodeLastJsonLine($output);
        if (!$process->isSuccessful()) {
            $message = $result['error']
                ?? (trim($process->getErrorOutput()) ?: $output);
            throw new \Exception("Prediction error: {$message}");
        }
        if (!$result || ($result['status'] ?? null) !== 'ok') {
            throw new \Exception('Prediction error: invalid scoped model response.');
        }

        $stored = 0;
        DB::transaction(function () use ($result, &$stored) {
            StockPrediction::whereIn('model_scope', ['universal', 'individual'])
                ->delete();

            $stocks = Stock::whereIn(
                'symbol',
                collect($result['results'])->pluck('symbol')
            )->get()->keyBy('symbol');

            foreach ($result['results'] as $stockResult) {
                $stock = $stocks->get($stockResult['symbol']);
                if (!$stock) {
                    continue;
                }
                foreach ($stockResult['predictions'] as $prediction) {
                    StockPrediction::updateOrCreate(
                        [
                            'stock_id' => $stock->id,
                            'model_type' => $prediction['model_type'],
                            'model_scope' => $prediction['model_scope'] ?? 'universal',
                            'target_date' => $prediction['target_date'],
                        ],
                        [
                            'predicted_price' => $prediction['predicted_price'],
                            'additional_metrics' => $prediction['additional_metrics'] ?? [],
                        ]
                    );
                    $stored++;
                }
            }
        });

        return [
            'stored' => $stored,
            'eligible_stocks' => count($result['results']),
        ];
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
