<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockPrediction;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    private const MIN_ELIGIBLE_DATAPOINTS = 30;

    public function index(Request $request)
    {
        $eligibility = in_array(
            $request->get('eligibility'),
            ['eligible', 'ineligible'],
            true
        ) ? $request->get('eligibility') : 'all';
        $sort = in_array(
            $request->get('sort'),
            ['datapoints_desc', 'datapoints_asc', 'symbol_asc'],
            true
        ) ? $request->get('sort') : 'symbol_asc';

        $stocks = Stock::where('is_active', true)
            ->withUsableDatapointCount()
            ->with(['predictions' => function ($query) {
                $query->orderBy('target_date', 'asc');
            }]);

        if ($eligibility === 'eligible') {
            $stocks->whereRaw(
                'stocks.usable_datapoints_count >= ?',
                [self::MIN_ELIGIBLE_DATAPOINTS]
            );
        } elseif ($eligibility === 'ineligible') {
            $stocks->whereRaw(
                'stocks.usable_datapoints_count < ?',
                [self::MIN_ELIGIBLE_DATAPOINTS]
            );
        }

        match ($sort) {
            'datapoints_desc' => $stocks->orderByDesc('datapoints_count')->orderBy('symbol'),
            'datapoints_asc' => $stocks->orderBy('datapoints_count')->orderBy('symbol'),
            default => $stocks->orderBy('symbol'),
        };

        $stocks = $stocks->paginate(15)->withQueryString();

        // Calculate some global metrics if needed
        $totalPredictions = StockPrediction::count();
        $latestPredictions = StockPrediction::with('stock')
            ->latest()
            ->take(5)
            ->get();

        $minimumDatapoints = self::MIN_ELIGIBLE_DATAPOINTS;

        return view('analysis.index', compact(
            'stocks',
            'totalPredictions',
            'latestPredictions',
            'eligibility',
            'sort',
            'minimumDatapoints'
        ));
    }
}
