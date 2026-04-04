<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockPrice;
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
            
        // You would typically fetch predictions here or via API
        $predictions = $stock->predictions()->orderBy('target_date', 'asc')->get();

        return view('stocks.show', compact('stock', 'historicalData', 'predictions'));
    }
    
    public function getChartData($symbol, Request $request)
    {
        $stock = Stock::where('symbol', $symbol)->firstOrFail();
        $range = $request->get('range', '1M'); // 1D, 1W, 1M, 1Y
        
        $query = StockPrice::where('stock_id', $stock->id)->orderBy('date', 'asc');
        
        // Filter based on range
        if ($range === '1W') {
            $query->where('date', '>=', now()->subWeek());
        } elseif ($range === '1M') {
            $query->where('date', '>=', now()->subMonth());
        } elseif ($range === '1Y') {
            $query->where('date', '>=', now()->subYear());
        }
        
        $prices = $query->get();
        return response()->json($prices);
    }
}
