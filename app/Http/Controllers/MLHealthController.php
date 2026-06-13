<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use App\Models\ModelTrainingJob;

class MLHealthController extends Controller
{
    public function check()
    {
        // Only allow authenticated users
        $this->middleware('auth');

        $queueSize = Queue::size('ml_training');
        $pendingJobs = ModelTrainingJob::where('status', 'queued')->count();
        $processingJobs = ModelTrainingJob::where('status', 'processing')->count();
        $failedJobsLastHour = ModelTrainingJob::where('status', 'failed')
            ->where('created_at', '>', now()->subHour())
            ->count();

        // Get last successful training
        $lastSuccessfulTraining = ModelTrainingJob::where('status', 'completed')
            ->latest('completed_at')
            ->first();

        // Get average training time
        $avgTrainingTime = ModelTrainingJob::whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->where('status', 'completed')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_time')
            ->value('avg_time');

        return response()->json([
            'success' => true,
            'data' => [
                'queue_size' => $queueSize,
                'pending_jobs' => $pendingJobs,
                'processing_jobs' => $processingJobs,
                'failed_jobs_last_hour' => $failedJobsLastHour,
                'models_cached' => Cache::get('model_cache_stats', 0),
                'last_training' => $lastSuccessfulTraining?->completed_at?->diffForHumans(),
                'average_training_time' => $avgTrainingTime ? round($avgTrainingTime / 60, 1) . ' minutes' : null,
                'system_load' => sys_getloadavg()[0] ?? null,
                'status' => $this->determineSystemStatus($queueSize, $failedJobsLastHour),
            ]
        ]);
    }

    private function determineSystemStatus($queueSize, $failedJobsLastHour): string
    {
        if ($failedJobsLastHour > 5) {
            return 'degraded';
        }
        if ($queueSize > 10) {
            return 'overloaded';
        }
        if ($queueSize > 5) {
            return 'busy';
        }
        return 'healthy';
    }
}
