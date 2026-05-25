<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockPrediction;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    public function index()
    {
        // Fetch stocks with their latest predictions
        $stocks = Stock::where('is_active', true)
            ->with(['predictions' => function ($query) {
                $query->orderBy('target_date', 'asc');
            }])
            ->get();

        // Calculate some global metrics if needed
        $totalPredictions = StockPrediction::count();
        $latestPredictions = StockPrediction::latest()->take(5)->get();

        return view('analysis.index', compact('stocks', 'totalPredictions', 'latestPredictions'));
    }
}
