<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockPrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_id',
        'model_type',
        'model_scope',
        'target_date',
        'predicted_price',
        'additional_metrics',
    ];

    protected $casts = [
        'additional_metrics' => 'array',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
}
