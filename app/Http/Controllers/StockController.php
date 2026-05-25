<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockPrice;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
        $stocks = Stock::where('is_active', true)->paginate(15);
        return view('stocks.index', compact('stocks'));
    }

    public function show($symbol)
    {
        $stock = Stock::where('symbol', $symbol)->firstOrFail();

        // Fetch historical data for chart
        $historicalData = StockPrice::where('stock_id', $stock->id)
            ->orderBy('date', 'asc')
            ->get();

        $predictions = $stock->predictions()
            ->orderBy('model_type')
            ->orderBy('target_date', 'asc')
            ->get()
            ->groupBy('model_type')
            ->map(fn ($group) => $group->first())
            ->values();

        $isInWatchlist = auth()->user()
            ->watchlists()
            ->where('stock_id', $stock->id)
            ->exists();

        $trendImage = File::exists(public_path($stock->symbol . '_trend.png'))
            ? asset($stock->symbol . '_trend.png') . '?v=' . filemtime(public_path($stock->symbol . '_trend.png'))
            : null;

        return view('stocks.show', compact('stock', 'historicalData', 'predictions', 'isInWatchlist', 'trendImage'));
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
            $lastNonEmptyLine = collect($lines)->reverse()->first(fn ($line) => trim((string) $line) !== '');
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
}
