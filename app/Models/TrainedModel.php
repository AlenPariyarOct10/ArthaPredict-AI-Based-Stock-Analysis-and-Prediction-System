<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainedModel extends Model
{
    protected $fillable = [
        'stock_id',
        'stock_symbol',
        'model_type',
        'model_scope',
        'model_path',
        'latest_path',
        'fingerprint',
        'training_date',
        'mse',
        'mae',
        'rmse',
        'mape',
        'r2',
        'directional_accuracy',
        'confidence_score',
        'training_loss',
        'config_json',
        'data_length',
        'is_active',
    ];

    protected $casts = [
        'config_json' => 'array',
        'training_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
}
