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
        // Get active stocks for overview
        $stocks = Stock::where('is_active', true)->with('prices')->take(6)->get();

        $chartStock = Stock::where('is_active', true)
            ->whereHas('prices')
            ->with(['prices' => function ($query) {
                $query->orderBy('date', 'desc')->limit(30);
            }])
            ->first();

        $marketTrend = $chartStock
            ? $chartStock->prices->sortBy('date')->values()
            : collect();

        $aiTopPick = StockPrediction::with('stock')
            ->whereHas('stock', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('target_date')
            ->first();

        return view('dashboard.index', compact('stocks', 'marketTrend', 'aiTopPick', 'chartStock'));
    }
}
