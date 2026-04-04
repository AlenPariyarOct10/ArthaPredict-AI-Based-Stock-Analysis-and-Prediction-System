<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;

class PredictionController extends Controller
{
    // This could also act as a bridge to Python ML scripts
    public function getPredictionData($symbol)
    {
        $stock = Stock::where('symbol', $symbol)->firstOrFail();
        
        $predictions = $stock->predictions()->orderBy('target_date', 'asc')->get();
        
        return response()->json([
            'symbol' => $stock->symbol,
            'predictions' => $predictions
        ]);
    }
    
    // Webhook/Endpoint for Python script to post predictions
    public function storePredictions(Request $request)
    {
        // Auth/Security logic missing here for brevity
        $validated = $request->validate([
            'symbol' => 'required|exists:stocks,symbol',
            'model_type' => 'required|string',
            'predictions' => 'required|array',
            'predictions.*.target_date' => 'required|date',
            'predictions.*.predicted_price' => 'required|numeric',
            'predictions.*.additional_metrics' => 'nullable|array'
        ]);
        
        $stock = Stock::where('symbol', $validated['symbol'])->first();
        
        foreach ($validated['predictions'] as $pred) {
            $stock->predictions()->updateOrCreate(
                [
                    'model_type' => $validated['model_type'],
                    'target_date' => $pred['target_date']
                ],
                [
                    'predicted_price' => $pred['predicted_price'],
                    'additional_metrics' => $pred['additional_metrics'] ?? null
                ]
            );
        }
        
        return response()->json(['message' => 'Predictions stored successfully']);
    }
}
