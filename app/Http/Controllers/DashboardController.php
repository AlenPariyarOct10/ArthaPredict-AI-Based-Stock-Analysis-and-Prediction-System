<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockPrice;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Get active stocks for overview
        $stocks = Stock::where('is_active', true)->with('prices')->take(6)->get();

        // Sample data for charts (e.g., market trend based on a dummy index or top stock)
        $marketTrend = StockPrice::whereHas('stock', function ($q) {
            $q->where('symbol', 'AAPL'); // Example
        })->orderBy('date', 'desc')->take(30)->get()->reverse()->values();

        return view('dashboard.index', compact('stocks', 'marketTrend'));
    }
}
