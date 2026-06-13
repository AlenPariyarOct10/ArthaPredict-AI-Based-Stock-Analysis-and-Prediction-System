<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockPrediction;
use App\Models\StockPrice;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        // Only include stocks that have at least one trained model
        $query = Stock::where('is_active', true)->whereHas('trainedModels', function($q) {
            $q->where('is_active', true);
        });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('symbol', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        $stocks = $query->paginate(15)->withQueryString();
        return view('stocks.index', compact('stocks', 'search'));
    }

    public function show($symbol)
    {
        $stock = Stock::where('symbol', $symbol)->firstOrFail();

        // Fetch historical data for chart
        $historicalData = StockPrice::where('stock_id', $stock->id)
            ->orderBy('date', 'asc')
            ->get();

        $latestPrice = StockPrice::where('stock_id', $stock->id)->orderBy('date', 'desc')->first();
        $latestDate = $latestPrice ? $latestPrice->date : null;

        $predictions = collect();
        if ($latestDate) {
            $predictions = $stock->predictions()
                ->where('target_date', '>', $latestDate)
                ->orderBy('target_date', 'asc')
                ->orderBy('model_type')
                ->get();
        }

        $isInWatchlist = auth()->user()
            ->watchlists()
            ->where('stock_id', $stock->id)
            ->exists();

        $trendImage = File::exists(public_path($stock->symbol . '_trend.png'))
            ? asset($stock->symbol . '_trend.png') . '?v=' . filemtime(public_path($stock->symbol . '_trend.png'))
            : null;

        return view('stocks.show', compact('stock', 'historicalData', 'predictions', 'isInWatchlist', 'trendImage'));
    }

    public function runPrediction(Request $request, $symbol, \App\Services\PredictionService $predictionService)
    {
        set_time_limit(300);
        $stock = Stock::where('symbol', $symbol)->firstOrFail();

        try {
            $predictions = $predictionService->generatePredictions($stock);

            if ($request->ajax() || $request->wantsJson()) {
                session()->flash('success', 'Stock price predictions updated successfully.');
                return response()->json([
                    'success' => true,
                    'message' => 'Stock price predictions updated successfully.',
                    'predictions' => $predictions
                ]);
            }

            return back()->with('success', 'Stock price predictions updated successfully.');

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Synchronous prediction failed for ' . $stock->symbol . ': ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An unexpected error occurred during prediction: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'An unexpected error occurred during prediction: ' . $e->getMessage());
        }
    }

    public function runMovingAverage($symbol)
    {

        $stock = Stock::where('symbol', $symbol)->firstOrFail();
        $pythonExecutable = 'python';
        $scriptPath = base_path('ml_service/simple_moving_average.py');
        $outputPath = public_path($stock->symbol . '_trend.png');

        $command = sprintf(
            '%s %s %s %s 2>&1',
            escapeshellcmd($pythonExecutable),
            escapeshellarg($scriptPath),
            escapeshellarg($stock->symbol),
            escapeshellarg($outputPath)
        );

        $output = shell_exec($command);

        if (!$output) {
            return back()->with('error', 'Failed to calculate SMA/EMA trend.');
        }

        $rawOutput = trim($output);
        $result = json_decode($rawOutput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Python logs/warnings can appear before JSON; decode the last non-empty line.
            $lines = preg_split('/\r\n|\r|\n/', $rawOutput);
            $lastNonEmptyLine = collect($lines)->reverse()->first(fn($line) => trim((string) $line) !== '');
            $result = $lastNonEmptyLine ? json_decode($lastNonEmptyLine, true) : null;
        }

        if (json_last_error() !== JSON_ERROR_NONE || isset($result['error'])) {
            return back()->with('error', $result['error'] ?? 'Failed to parse moving average output.');
        }

        if (!File::exists($outputPath)) {
            return back()->with('error', 'Trend calculation completed but image was not generated.');
        }

        return back()->with('success', 'SMA/EMA trend image updated successfully.');
    }

    public function getChartData($symbol, Request $request)
    {
        $stock = Stock::where('symbol', $symbol)->firstOrFail();
        $range = $request->get('range', '1M');

        $query = StockPrice::where('stock_id', $stock->id)->orderBy('date', 'asc');

        if ($range === '1M') {
            $query->where('date', '>=', now()->subMonth());
        } elseif ($range === '3M') {
            $query->where('date', '>=', now()->subMonths(3));
        } elseif ($range === '1Y') {
            $query->where('date', '>=', now()->subYear());
        }

        $prices = $query->get();
        return response()->json($prices);
    }

    /**
     * Get predictions for a stock (AJAX endpoint)
     */
    public function getPredictions($symbol)
    {
        $stock = Stock::where('symbol', $symbol)->firstOrFail();

        $predictions = StockPrediction::where('stock_id', $stock->id)
            ->orderBy('target_date', 'asc')
            ->get();

        return response()->json([
            'symbol' => $stock->symbol,
            'predictions' => $predictions
        ]);
    }

    /**
     * Get model metrics for a stock (AJAX endpoint)
     */
    public function getModelMetrics($symbol)
    {
        $stock = Stock::where('symbol', $symbol)->firstOrFail();

        $activeModels = \App\Models\TrainedModel::where('stock_id', $stock->id)
            ->where('is_active', true)
            ->get();

        $metrics = [];
        foreach ($activeModels as $model) {
            $metrics[$model->model_type] = [
                'mse' => $model->mse,
                'mae' => $model->mae,
                'rmse' => $model->rmse,
                'mape' => $model->mape,
                'directional_accuracy' => $model->directional_accuracy,
                'confidence_score' => $model->confidence_score,
                'training_date' => $model->training_date,
            ];
        }

        return response()->json([
            'symbol' => $stock->symbol,
            'metrics' => $metrics
        ]);
    }

    /**
     * Search stocks (AJAX endpoint)
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $stocks = Stock::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('symbol', 'LIKE', "%{$query}%")
                    ->orWhere('name', 'LIKE', "%{$query}%");
            })
            ->limit(10)
            ->get();

        return response()->json($stocks);
    }

    /**
     * Get popular stocks (AJAX endpoint)
     */
    public function popular()
    {
        $stocks = Stock::where('is_active', true)
            ->withCount('watchlists')
            ->orderBy('watchlists_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json($stocks);
    }

    /**
     * Get latest predictions across all stocks (AJAX endpoint)
     */
    public function latestPredictions()
    {
        $predictions = StockPrediction::with('stock')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json($predictions);
    }

    /**
     * Export stock historical data as CSV
     */
    public function exportData($symbol)
    {
        $stock = Stock::where('symbol', $symbol)->firstOrFail();

        $historicalData = StockPrice::where('stock_id', $stock->id)
            ->orderBy('date', 'asc')
            ->get();

        if ($historicalData->isEmpty()) {
            return back()->with('error', 'No historical data available for export.');
        }

        $filename = "{$stock->symbol}_historical_data_" . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($historicalData) {
            $file = fopen('php://output', 'w');

            // CSV Header
            fputcsv($file, ['Date', 'Open', 'High', 'Low', 'Close', 'Volume']);

            // CSV Data
            foreach ($historicalData as $price) {
                fputcsv($file, [
                    $price->date,
                    $price->open,
                    $price->high,
                    $price->low,
                    $price->close,
                    $price->volume,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
