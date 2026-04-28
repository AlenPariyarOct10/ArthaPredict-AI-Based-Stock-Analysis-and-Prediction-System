<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stock;
use App\Models\Feedback;
use App\Models\StockPrediction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function dashboard()
    {
        $usersCount = User::count();
        $stocksCount = Stock::count();
        $pendingFeedbackCount = Feedback::where('status', 'pending')->count();
        $stocks = Stock::where('is_active', true)->get();
        
        return view('admin.dashboard', compact('usersCount', 'stocksCount', 'pendingFeedbackCount', 'stocks'));
    }
    
    public function trainModel(Request $request)
    {
        $request->validate([
            'stock_id' => 'required|exists:stocks,id'
        ]);

        $stock = Stock::findOrFail($request->stock_id);
        $symbol = $stock->symbol;

        // Path to your python executable and script
        $pythonExecutable = 'python';
        $scriptPath = base_path('ml_service/predict.py');

        // Execute the python script
        $command = escapeshellcmd("$pythonExecutable \"$scriptPath\" $symbol");
        $output = shell_exec($command);

        if (!$output) {
            return back()->with('error', 'Failed to execute prediction script.');
        }

        $result = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE || isset($result['error'])) {
            $errorMessage = $result['error'] ?? 'Unknown error parsing python script output.';
            return back()->with('error', 'Training failed: ' . $errorMessage);
        }

        // Delete existing predictions for this stock to insert fresh ones
        StockPrediction::where('stock_id', $stock->id)->delete();

        if (isset($result['predictions']) && is_array($result['predictions'])) {
            foreach ($result['predictions'] as $prediction) {
                StockPrediction::create([
                    'stock_id' => $stock->id,
                    'model_type' => $prediction['model_type'],
                    'target_date' => $prediction['target_date'],
                    'predicted_price' => $prediction['predicted_price'],
                    'additional_metrics' => $prediction['additional_metrics']
                ]);
            }
        }

        return back()->with('success', "Model successfully trained and predictions updated for $symbol.");
    }
}
