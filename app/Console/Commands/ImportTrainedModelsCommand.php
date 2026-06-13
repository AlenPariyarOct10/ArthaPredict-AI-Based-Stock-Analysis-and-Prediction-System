<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportTrainedModelsCommand extends Command
{
    protected $signature = 'models:import-metadata';

    protected $description = 'Imports metadata.json from ml_service into the trained_models table';

    public function handle(\App\Services\ModelRegistryService $registry)
    {
        $path = base_path('ml_service/models/metadata.json');
        if (!\Illuminate\Support\Facades\File::exists($path)) {
            $this->error('metadata.json not found.');
            return;
        }

        $content = \Illuminate\Support\Facades\File::get($path);
        $data = json_decode($content, true);

        if (!isset($data['models'])) {
            $this->error('Invalid metadata.json structure.');
            return;
        }

        $this->info('Importing models from metadata.json...');

        foreach ($data['models'] as $key => $info) {
            $parts = explode('_', $key);
            $symbol = $parts[0];
            $modelType = count($parts) > 1 ? strtolower($parts[1]) : 'lstm';

            $stock = \App\Models\Stock::where('symbol', $symbol)->first();
            if (!$stock) {
                $this->warn("Stock symbol {$symbol} not found. Skipping.");
                continue;
            }

            $metrics = $info['metrics'] ?? [];
            $mapeStr = $metrics['mape'] ?? null;
            $mape = $mapeStr ? floatval(str_replace('%', '', $mapeStr)) : null;

            $dirAccStr = $metrics['directional_accuracy'] ?? null;
            $dirAcc = $dirAccStr ? floatval(str_replace('%', '', $dirAccStr)) : null;

            $confScoreStr = $metrics['confidence_score'] ?? null;
            $confScore = $confScoreStr ? floatval(str_replace('%', '', $confScoreStr)) : null;

            $registry->registerModel($stock, [
                'model_type' => $modelType,
                'model_path' => $info['path'] ?? null,
                'latest_path' => $info['latest_path'] ?? null,
                'fingerprint' => $info['fingerprint'] ?? null,
                'training_date' => $info['training_date'] ?? null,
                'config_json' => $info['config'] ?? null,
                'data_length' => $info['data_length'] ?? null,
                'metrics' => [
                    'mse' => $metrics['mse'] ?? null,
                    'mae' => $metrics['mae'] ?? null,
                    'rmse' => $metrics['rmse'] ?? null,
                    'mape' => $mape,
                    'directional_accuracy' => $dirAcc,
                    'confidence_score' => $confScore,
                    'training_loss' => $metrics['training_loss'] ?? null,
                ]
            ]);

            $this->info("Imported {$modelType} for {$symbol}.");
        }

        $this->info('Import complete.');
    }
}
