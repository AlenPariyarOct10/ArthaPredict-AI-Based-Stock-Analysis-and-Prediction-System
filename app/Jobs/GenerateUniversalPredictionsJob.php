<?php

namespace App\Jobs;

use App\Models\ModelTrainingJob;
use App\Models\Stock;
use App\Services\PredictionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateUniversalPredictionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 1;

    public function __construct(
        public ?int $stockId,
        public int $trainingJobId
    ) {
    }

    public function handle(PredictionService $predictionService): void
    {
        $job = ModelTrainingJob::findOrFail($this->trainingJobId);
        $stocks = $this->stockId
            ? Stock::whereKey($this->stockId)->get()
            : Stock::where('is_active', true)->orderBy('symbol')->get();

        $job->update([
            'status' => 'processing',
            'started_at' => now(),
            'current_stage' => 'generating predictions',
            'total_rows' => $stocks->count(),
            'processed_rows' => 0,
        ]);

        if (!$this->stockId) {
            $result = $predictionService->generatePredictionsForAll();
            $job->update([
                'total_rows' => $result['eligible_stocks'],
                'processed_rows' => $result['eligible_stocks'],
                'current_stage' => 'stored all predictions',
                'meta' => array_merge($job->meta ?? [], [
                    'eligible_stocks' => $result['eligible_stocks'],
                    'skipped_stocks' => max(
                        0,
                        $stocks->count() - $result['eligible_stocks']
                    ),
                    'stored_predictions' => $result['stored'],
                ]),
            ]);
        } else {
            foreach ($stocks as $index => $stock) {
                $predictionService->generatePredictions($stock);
                $job->update([
                    'processed_rows' => $index + 1,
                    'current_stage' => "predicted {$stock->symbol}",
                ]);
            }
        }

        $job->update([
            'status' => 'completed',
            'completed_at' => now(),
            'current_stage' => 'completed',
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Universal prediction generation failed', [
            'job_id' => $this->trainingJobId,
            'error' => $exception->getMessage(),
        ]);
        ModelTrainingJob::whereKey($this->trainingJobId)->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'completed_at' => now(),
        ]);
    }
}
