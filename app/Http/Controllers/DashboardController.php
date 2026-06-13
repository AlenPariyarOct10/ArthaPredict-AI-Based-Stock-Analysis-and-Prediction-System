<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockPrice;
use App\Models\StockPrediction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Get active stocks for overview – only load the last 2 prices per stock
        // (latest + previous) which is all we need for price & change display.
        $stocks = Stock::where('is_active', true)
            ->whereHas('trainedModels', function($q) {
                $q->where('is_active', true);
            })
            ->with(['prices' => function ($query) {
                $query->orderBy('date', 'desc')->limit(2);
            }])
            ->take(6)
            ->get()
            // Sort by price change percent (descending)
            ->sortByDesc(function ($stock) {
                $prices = $stock->prices;
                if ($prices->count() > 1) {
                    $latest = $prices[0]->close;
                    $prev = $prices[1]->close;
                    return ($latest - $prev) / $prev;
                }
                return 0;
            })
            ->values();

        $chartStock = Stock::where('is_active', true)
            ->whereHas('prices')
            ->whereHas('trainedModels', function($q) {
                $q->where('is_active', true);
            })
            ->with(['prices' => function ($query) {
                $query->orderBy('date', 'desc')->limit(30);
            }])
            ->first();

        $marketTrend = $chartStock
            ? $chartStock->prices->sortBy('date')->values()
            : collect();

        // Determine AI Top Pick: choose prediction with highest price increase relative to current price
        $aiTopPick = StockPrediction::with(['stock' => function ($q) {
                $q->where('is_active', true)
                  ->with(['prices' => function ($pq) {
                        $pq->orderBy('date', 'desc')->limit(1);
                    }]);
            }])
            ->get()
            ->filter(function ($pred) {
                return $pred->stock && $pred->stock->prices->isNotEmpty();
            })
            ->map(function ($pred) {
                $latestPrice = $pred->stock->prices->first()->close ?? null;
                $pred->price_change = $latestPrice ? $pred->predicted_price - $latestPrice : null;
                return $pred;
            })
            ->sortByDesc('price_change')
            ->first();

        return view('dashboard.index', compact('stocks', 'marketTrend', 'aiTopPick', 'chartStock'));
    }
}
