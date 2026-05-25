<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stock;
use App\Models\Feedback;
use App\Models\StockPrediction;
use App\Models\ModelTrainingJob;
use App\Jobs\TrainModelJob;
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
        $recentJobs = ModelTrainingJob::with('stock')
            ->latest()
            ->take(10)
            ->get();
        
        return view('admin.dashboard', compact('usersCount', 'stocksCount', 'pendingFeedbackCount', 'stocks', 'recentJobs'));
    }
    
    public function trainModel(Request $request)
    {
        $request->validate([
            'stock_id' => 'required|exists:stocks,id'
        ]);

        $stock = Stock::findOrFail($request->stock_id);

        // Check if there is an active job (queued or processing) for this stock
        $activeJob = ModelTrainingJob::where('stock_id', $stock->id)
            ->whereIn('status', ['queued', 'processing'])
            ->first();

        if ($activeJob) {
            return back()->with('error', "Training is already in progress for {$stock->symbol}.");
        }

        // Create a new ModelTrainingJob record
        $jobRecord = ModelTrainingJob::create([
            'stock_id' => $stock->id,
            'user_id' => auth()->id(),
            'status' => 'queued',
        ]);

        // Dispatch the queue job
        TrainModelJob::dispatch($stock->id, auth()->id(), $jobRecord->id);

        return back()->with('success', "Model training has been successfully queued in the background for {$stock->symbol}.");
    }

    public function getTrainingStatus()
    {
        $recentJobs = ModelTrainingJob::with('stock')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'stock_id' => $job->stock_id,
                    'symbol' => $job->stock->symbol,
                    'name' => $job->stock->name,
                    'status' => $job->status,
                    'error_message' => $job->error_message,
                    'updated_at' => $job->updated_at->diffForHumans(),
                    'created_at_formatted' => $job->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'jobs' => $recentJobs
        ]);
    }
}
