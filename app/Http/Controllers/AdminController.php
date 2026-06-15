<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stock;
use App\Models\Feedback;
use App\Models\StockPrediction;
use App\Models\ModelTrainingJob;
use App\Models\AppSetting;
use App\Jobs\TrainUniversalModelJob;
use App\Jobs\TrainIndividualModelJob;
use App\Jobs\GenerateUniversalPredictionsJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Cache heavy queries for 5 minutes
        $stats = Cache::remember('admin_dashboard_stats', 300, function () {
            return [
                'users_count' => User::count(),
                'stocks_count' => Stock::count(),
                'active_stocks_count' => Stock::where('is_active', true)->count(),
                'pending_feedback_count' => Feedback::where('status', 'pending')->count(),
                'total_trained_models' => \App\Models\TrainedModel::count(),
                'total_predictions' => StockPrediction::count(),
                'recent_predictions' => StockPrediction::with('stock')
                    ->latest()
                    ->take(5)
                    ->get(),
            ];
        });

        $stocks = Stock::where('is_active', true)
            ->orderBy('symbol')
            ->get();

        $recentJobs = ModelTrainingJob::with('stock')
            ->latest()
            ->take(20)
            ->get();

        // Get training statistics
        $trainingStats = [
            'queued' => ModelTrainingJob::where('status', 'queued')->count(),
            'processing' => ModelTrainingJob::where('status', 'processing')->count(),
            'completed_today' => ModelTrainingJob::where('status', 'completed')
                ->whereDate('completed_at', today())
                ->count(),
            'failed_today' => ModelTrainingJob::where('status', 'failed')
                ->whereDate('completed_at', today())
                ->count(),
            'average_training_time' => ModelTrainingJob::whereNotNull('started_at')
                ->whereNotNull('completed_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_time')
                ->value('avg_time'),
        ];

        // Merge all data into a single array for the view
        $viewData = array_merge($stats, [
            'stocks' => $stocks,
            'recentJobs' => $recentJobs,
            'trainingStats' => $trainingStats,
        ]);


        return view('admin.dashboard', $viewData);
    }

    /**
     * Train model for a specific stock
     */
    public function trainModel(Request $request)
    {
        $validated = $request->validate([
            'stock_id' => 'required|exists:stocks,id',
            'model_type' => 'required|in:all,lstm,xgboost,random_forest,moving_average',
        ]);

        $stock = Stock::findOrFail($validated['stock_id']);
        $jobRecord = ModelTrainingJob::create([
            'stock_id' => $stock->id,
            'user_id' => auth()->id(),
            'status' => 'queued',
            'current_stage' => 'queued',
            'total_rows' => 1,
            'processed_rows' => 0,
            'meta' => [
                'model_type' => $validated['model_type'],
                'model_scope' => 'individual',
                'is_universal' => false,
            ],
        ]);

        TrainIndividualModelJob::dispatch(
            $stock->id,
            $validated['model_type'],
            auth()->id(),
            $jobRecord->id
        )->onQueue('ml_training_universal');

        $message = "{$stock->symbol} individual model training was queued.";
        return $request->wantsJson()
            ? response()->json([
                'success' => true,
                'message' => $message,
                'job_id' => $jobRecord->id,
            ])
            : back()->with('success', $message);
    }

    /**
     * Get training status for all or specific stock
     */
    public function getTrainingStatus(Request $request)
    {
        $stockId = $request->get('stock_id');

        $query = ModelTrainingJob::with('stock');

        if ($stockId) {
            $query->where('stock_id', $stockId);
        }

        $recentJobs = $query->latest()
            ->take(50)
            ->get()
            ->map(function ($job) {
                return $this->formatJobForResponse($job);
            });

        // Get summary statistics
        $summary = [
            'total_queued' => ModelTrainingJob::where('status', 'queued')->count(),
            'total_processing' => ModelTrainingJob::where('status', 'processing')->count(),
            'completed_last_24h' => ModelTrainingJob::where('status', 'completed')
                ->where('completed_at', '>=', now()->subDay())
                ->count(),
            'failed_last_24h' => ModelTrainingJob::where('status', 'failed')
                ->where('completed_at', '>=', now()->subDay())
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'jobs' => $recentJobs,
            'summary' => $summary,
            'last_updated' => now()->toIso8601String(),
        ]);
    }

    /**
     * Cancel a training job
     */
    public function cancelTraining(Request $request)
    {
        $request->validate([
            'job_id' => 'required|exists:model_training_jobs,id'
        ]);

        $job = ModelTrainingJob::findOrFail($request->job_id);

        if (!in_array($job->status, ['queued', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel job that is already ' . $job->status
            ], 400);
        }

        DB::beginTransaction();
        try {
            $job->update([
                'status' => 'cancelled',
                'completed_at' => now(),
                'error_message' => 'Cancelled by admin: ' . auth()->user()->email,
            ]);

            // Remove from queue if possible (requires queue driver support)
            // This is Redis-specific; adjust based on your queue driver
            if (config('queue.default') === 'redis') {
                $this->removeFromRedisQueue($job->id);
            }

            DB::commit();

            Log::info("Training job cancelled", [
                'job_id' => $job->id,
                'stock_id' => $job->stock_id,
                'cancelled_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Training job cancelled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry failed training jobs
     */
    public function retryFailedTraining(Request $request)
    {
        $request->validate([
            'job_id' => 'required|exists:model_training_jobs,id'
        ]);

        $failedJob = ModelTrainingJob::findOrFail($request->job_id);

        if ($failedJob->status !== 'failed') {
            return back()->with('error', 'Only failed jobs can be retried.');
        }

        $failedMeta = $failedJob->meta ?? [];
        DB::beginTransaction();
        try {
            // Create new job record
            $newJob = ModelTrainingJob::create([
                'stock_id' => $failedJob->stock_id,
                'user_id' => auth()->id(),
                'status' => 'queued',
                'meta' => array_merge($failedMeta, [
                    'retry_of_job' => $failedJob->id,
                    'original_error' => $failedJob->error_message,
                    'triggered_by' => auth()->user()->email,
                ])
            ]);

            if (($failedMeta['model_type'] ?? null) === 'universal_predictions') {
                GenerateUniversalPredictionsJob::dispatch(
                    $failedJob->stock_id,
                    $newJob->id
                )->onQueue('ml_training_universal');
            } elseif (($failedMeta['model_scope'] ?? null) === 'individual') {
                TrainIndividualModelJob::dispatch(
                    $failedJob->stock_id,
                    $failedMeta['model_type'],
                    auth()->id(),
                    $newJob->id
                )->onQueue('ml_training_universal');
            } else {
                TrainUniversalModelJob::dispatch(
                    $failedMeta['model_type'],
                    auth()->id(),
                    $newJob->id
                )->onQueue('ml_training_universal');
            }

            DB::commit();

            Log::info("Retrying failed training job", [
                'original_job_id' => $failedJob->id,
                'new_job_id' => $newJob->id,
                'stock_id' => $failedJob->stock_id
            ]);

            return back()->with('success', 'Retry job has been queued successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to retry job: ' . $e->getMessage());
        }
    }

    /**
     * Bulk train multiple stocks
     */
    public function bulkTrain(Request $request)
    {
        $validated = $request->validate([
            'model_type' => 'required|in:all,lstm,xgboost,random_forest,moving_average',
        ]);

        $stocks = Stock::where('is_active', true)
            ->whereHas('prices')
            ->orderBy('symbol')
            ->get();
        foreach ($stocks as $stock) {
            $jobRecord = ModelTrainingJob::create([
                'stock_id' => $stock->id,
                'user_id' => auth()->id(),
                'status' => 'queued',
                'current_stage' => 'queued',
                'total_rows' => 1,
                'processed_rows' => 0,
                'meta' => [
                    'model_type' => $validated['model_type'],
                    'model_scope' => 'individual',
                    'is_universal' => false,
                    'bulk' => true,
                ],
            ]);
            TrainIndividualModelJob::dispatch(
                $stock->id,
                $validated['model_type'],
                auth()->id(),
                $jobRecord->id
            )->onQueue('ml_training_universal');
        }

        return back()->with(
            'success',
            "Queued individual {$validated['model_type']} training for {$stocks->count()} stocks."
        );
    }

    /**
     * Train universal model (LSTM/XGBoost/Random Forest) across all stocks
     */
    public function trainUniversalModel(Request $request)
    {
        $request->validate([
            'model_type' => 'required|in:all,lstm,xgboost,random_forest,moving_average',
        ]);

        $modelType = $request->model_type;
        $modelTypeDisplay = match($modelType) {
            'lstm' => 'LSTM',
            'xgboost' => 'XGBoost',
            'random_forest' => 'Random Forest',
            'moving_average' => 'Moving Average',
            'all' => 'All algorithms',
        };

        // Check if there is an active universal model training job
        $activeJob = ModelTrainingJob::where('meta->is_universal', true)
            ->where('meta->model_type', '!=', 'universal_predictions')
            ->whereIn('status', ['queued', 'processing'])
            ->first();

        if ($activeJob) {
            $currentStage = $activeJob->current_stage ?: 'initializing';
            $message = $activeJob->status === 'queued'
                ? "{$modelTypeDisplay} universal model training is queued and will start shortly."
                : "{$modelTypeDisplay} universal model training is currently in progress ({$currentStage}).";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'job_status' => $activeJob->status
                ], 409);
            }

            return back()->with('info', $message);
        }

        // Create job record – include total_rows/processed_rows for progress UI
        $jobRecord = ModelTrainingJob::create([
            'user_id' => auth()->id(),
            'status' => 'queued',
            'current_stage' => 'queued',
            // Use a dummy total_rows of 1 so the UI can show 0% → 100% progress
            'total_rows' => 1,
            'processed_rows' => 0,
            'meta' => [
                'model_type' => $modelType,
                'is_universal' => true,
            ]
        ]);

        // Dispatch the universal model training job
        TrainUniversalModelJob::dispatch($modelType, auth()->id(), $jobRecord->id)
            ->onQueue('ml_training_universal');

        $message = "{$modelTypeDisplay} universal model training has been queued in the background. This may take several minutes.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'job_id' => $jobRecord->id
            ]);
        }

        return back()->with('success', $message);
    }

    public function generateUniversalPredictions(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|in:all,single',
            'stock_id' => 'nullable|required_if:scope,single|exists:stocks,id',
        ]);

        $stockId = $validated['scope'] === 'single'
            ? (int) $validated['stock_id']
            : null;

        $jobRecord = ModelTrainingJob::create([
            'stock_id' => $stockId,
            'user_id' => auth()->id(),
            'status' => 'queued',
            'current_stage' => 'queued',
            'total_rows' => $stockId ? 1 : Stock::where('is_active', true)->count(),
            'processed_rows' => 0,
            'meta' => [
                'model_type' => 'universal_predictions',
                'is_universal' => true,
                'scope' => $validated['scope'],
            ],
        ]);

        GenerateUniversalPredictionsJob::dispatch($stockId, $jobRecord->id)
            ->onQueue('ml_training_universal');

        return back()->with(
            'success',
            $stockId
                ? 'Universal predictions were queued for the selected stock.'
                : 'Universal predictions were queued for all active stocks.'
        );
    }

    /**
     * Helper: Check if we can start new training
     */
    private function canStartTraining(Stock $stock, bool $forceRetrain): bool
    {
        // Check system load
        $processingJobs = ModelTrainingJob::where('status', 'processing')->count();

        // Allow max 3 concurrent training jobs
        if ($processingJobs >= 3 && !$forceRetrain) {
            return false;
        }

        // Check queue size
        $queueSize = Cache::get('ml_training_queue_size', 0);
        if ($queueSize > 10 && !$forceRetrain) {
            return false;
        }

        return true;
    }

    /**
     * Helper: Calculate total rows for progress tracking
     */
    private function calculateTotalRows(Stock $stock): int
    {
        // Estimate based on historical data and model complexity
        $priceCount = DB::table('stock_prices')
            ->where('stock_id', $stock->id)
            ->count();

        // LSTM: epochs * training_samples
        // XGBoost: n_estimators * training_samples
        $lstmEpochs = 50;
        $xgbEstimators = 50;
        $sequenceLength = min(20, max(5, $priceCount / 4));

        $lstmRows = $lstmEpochs * max(1, int(($priceCount - $sequenceLength) * 0.8));
        $xgbRows = $xgbEstimators * max(1, int(($priceCount - 10) * 0.8));

        return $lstmRows + $xgbRows;
    }

    /**
     * Helper: Get queue name based on priority
     */
    private function getQueueName(string $priority): string
    {
        return match($priority) {
            'high' => 'ml_training_high',
            'low' => 'ml_training_low',
            default => 'ml_training',
        };
    }

    /**
     * Helper: Format job for JSON response
     */
    private function formatJobForResponse(ModelTrainingJob $job): array
    {
        $progressPct = 0;
        if ($job->total_rows > 0) {
            $progressPct = round(($job->processed_rows / $job->total_rows) * 100);
        }

        // Calculate estimated time remaining
        $estimatedRemaining = null;
        if ($job->status === 'processing' && $job->processed_rows > 0 && $job->started_at) {
            $elapsedSeconds = now()->diffInSeconds($job->started_at);
            $rowsProcessed = $job->processed_rows;
            $rowsRemaining = max(0, $job->total_rows - $rowsProcessed);

            if ($rowsProcessed > 0) {
                $secondsPerRow = $elapsedSeconds / $rowsProcessed;
                $estimatedSeconds = $secondsPerRow * $rowsRemaining;
                $estimatedRemaining = now()->addSeconds($estimatedSeconds)->diffForHumans();
            }
        }

        // Determine display values – universal jobs have no associated stock
        $symbol = $job->stock ? $job->stock->symbol : ($job->meta['model_type'] ?? 'UNIVERSAL');
        $name   = $job->stock ? $job->stock->name   : 'Universal Model';

        return [
            'id' => $job->id,
            'stock_id' => $job->stock_id,
            'symbol' => $symbol,
            'name' => $name,
            'status' => $job->status,
            'status_badge' => $this->getStatusBadge($job->status),
            'total_rows' => $job->total_rows,
            'processed_rows' => $job->processed_rows,
            'remaining_rows' => max(0, $job->total_rows - $job->processed_rows),
            'current_stage' => $job->current_stage ?? 'initializing',
            'progress_pct' => min(100, $progressPct),
            'error_message' => $job->error_message,
            'estimated_remaining' => $estimatedRemaining,
            'started_at' => $job->started_at?->diffForHumans(),
            'completed_at' => $job->completed_at?->diffForHumans(),
            'updated_at' => $job->updated_at->diffForHumans(),
            'created_at_formatted' => $job->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Helper: Get status badge HTML class
     */
    private function getStatusBadge(string $status): string
    {
        return match($status) {
            'queued' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            'cancelled' => 'secondary',
            default => 'light',
        };
    }

    /**
     * Helper: Remove job from Redis queue (if using Redis)
     */
    private function removeFromRedisQueue(int $jobId): void
    {
        if (!extension_loaded('redis')) {
            return;
        }

        try {
            $redis = \Redis::connection();
            // Implementation depends on your queue structure
            // This is a placeholder for actual implementation
        } catch (\Exception $e) {
            Log::warning("Failed to remove job from Redis queue", ['job_id' => $jobId]);
        }
    }

    /**
     * Helper: Cancel an existing training job
     */
    private function cancelTrainingJob(ModelTrainingJob $job): void
    {
        $job->update([
            'status' => 'cancelled',
            'completed_at' => now(),
            'error_message' => 'Cancelled by new training request'
        ]);

        Log::info("Training job cancelled for retraining", [
            'job_id' => $job->id,
            'stock_id' => $job->stock_id
        ]);
    }

    /**
     * Show the logo settings form
     */
    public function showLogoSettings()
    {
        $settings = AppSetting::where('group', 'general')->get()->keyBy('key');
        return view('admin.logo-settings', compact('settings'));
    }

    /**
     * Update the application logo
     */
    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        try {
            // Delete old logo if exists
            $oldLogo = AppSetting::get('app_logo');
            if ($oldLogo && !filter_var($oldLogo, FILTER_VALIDATE_URL) && Storage::exists('public/' . $oldLogo)) {
                Storage::delete('public/' . $oldLogo);
            }

            // Store new logo
            $path = $request->file('logo')->store('logos', 'public');

            // Update setting
            AppSetting::set('app_logo', $path, 'image', 'general', 'Application Logo', 'The main logo displayed in the sidebar and landing page.');

            // Clear cache
            Cache::forget('app_settings');

            return back()->with('success', 'Logo updated successfully!');
        } catch (\Exception $e) {
            Log::error('Logo upload failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to upload logo: ' . $e->getMessage());
        }
    }

    /**
     * Reset the logo to default
     */
    public function resetLogo()
    {
        try {
            // Delete current logo if exists
            $currentLogo = AppSetting::get('app_logo');
            if ($currentLogo && !filter_var($currentLogo, FILTER_VALIDATE_URL) && Storage::exists('public/' . $currentLogo)) {
                Storage::delete('public/' . $currentLogo);
            }

            // Reset to default
            AppSetting::set('app_logo', 'assets/images/Logo.png', 'image', 'general', 'Application Logo', 'The main logo displayed in the sidebar and landing page.');

            Cache::forget('app_settings');

            return back()->with('success', 'Logo reset to default successfully!');
        } catch (\Exception $e) {
            Log::error('Logo reset failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to reset logo: ' . $e->getMessage());
        }
    }
}
