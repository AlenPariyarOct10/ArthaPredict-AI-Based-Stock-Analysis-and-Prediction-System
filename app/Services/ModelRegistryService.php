<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\TrainedModel;

class ModelRegistryService
{
    public function registerUniversalModel(array $data): TrainedModel
    {
        TrainedModel::whereNull('stock_id')
            ->where('model_type', $data['model_type'])
            ->where('model_scope', 'universal')
            ->update(['is_active' => false]);

        $metrics = $data['metrics'] ?? [];

        return TrainedModel::create([
            'stock_id' => null,
            'stock_symbol' => 'UNIVERSAL',
            'model_type' => $data['model_type'],
            'model_scope' => 'universal',
            'model_path' => $data['path'] ?? null,
            'latest_path' => $data['latest_path'] ?? null,
            'fingerprint' => $data['fingerprint'] ?? null,
            'training_date' => $data['training_date'] ?? now(),
            'data_length' => $data['data_length'] ?? null,
            'config_json' => $data['config'] ?? null,
            'is_active' => true,
            'mse' => $metrics['mse'] ?? null,
            'mae' => $metrics['mae'] ?? null,
            'rmse' => $metrics['rmse'] ?? null,
            'mape' => $metrics['mape'] ?? null,
            'r2' => $metrics['r2'] ?? null,
            'directional_accuracy' => isset($metrics['directional_accuracy'])
                ? floatval(str_replace('%', '', $metrics['directional_accuracy']))
                : null,
            'confidence_score' => null,
            'training_loss' => $metrics['training_loss'] ?? null,
        ]);
    }

    /**
     * Register a new trained model.
     * Deactivates previous models of the same type for the stock.
     */
    public function registerModel(Stock $stock, array $data): TrainedModel
    {
        // Deactivate old models of this type
        TrainedModel::where('stock_id', $stock->id)
            ->where('model_type', $data['model_type'])
            ->where('model_scope', 'individual')
            ->update(['is_active' => false]);

        $metrics = $data['metrics'] ?? [];

        $payload = [
            'stock_id'             => $stock->id,
            'stock_symbol'         => $stock->symbol,
            'model_type'           => $data['model_type'],
            'model_scope'          => 'individual',

            // Fix: map Python keys → DB column names
            'model_path'           => $data['path']        ?? $data['model_path']    ?? null,
            'latest_path'          => $data['latest_path'] ?? null,
            'fingerprint'          => $data['fingerprint'] ?? null,
            'training_date'        => $data['training_date'] ?? now(),
            'data_length'          => $data['data_length'] ?? null,
            'config_json'          => $data['config']      ?? $data['config_json']   ?? null,

            'is_active'            => true,

            // Metrics
            'mse'                  => $metrics['mse']  ?? null,
            'mae'                  => $metrics['mae']  ?? null,
            'rmse'                 => $metrics['rmse'] ?? null,
            'mape'                 => isset($metrics['mape'])
                ? floatval(str_replace('%', '', $metrics['mape']))
                : null,
            'r2'                   => $metrics['r2'] ?? $metrics['r2_score'] ?? null,
            'directional_accuracy' => isset($metrics['directional_accuracy'])
                ? floatval(str_replace('%', '', $metrics['directional_accuracy']))
                : null,
            'confidence_score'     => isset($metrics['confidence_score'])
                ? floatval(str_replace('%', '', $metrics['confidence_score']))
                : null,
            'training_loss'        => $metrics['training_loss'] ?? null,
        ];

        return TrainedModel::create($payload);
    }

    /**
     * Get the latest active model for a stock.
     */
    public function getLatestModel(Stock $stock, string $modelType): ?TrainedModel
    {
        return TrainedModel::where('stock_id', $stock->id)
            ->where('model_type', $modelType)
            ->where('model_scope', 'individual')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Update metrics for a specific model.
     */
    public function updateMetrics(TrainedModel $model, array $metrics): bool
    {
        return $model->update($metrics);
    }
}
