<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'name',
        'sector',
        'exchange',
        'is_active',
        'usable_datapoints_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'usable_datapoints_count' => 'integer',
    ];

    public function prices()
    {
        return $this->hasMany(StockPrice::class);
    }

    public function usablePrices()
    {
        return $this->hasMany(StockPrice::class)
            ->where(function ($query) {
                $query->where('close', '>=', 10)
                    ->orWhereRaw(
                        '(SELECT MAX(sp_max.close) FROM stock_prices sp_max '
                        . 'WHERE sp_max.stock_id = stock_prices.stock_id) < 50'
                    );
            });
    }

    public function scopeWithUsableDatapointCount($query)
    {
        return $query
            ->select('stocks.*')
            ->selectRaw('stocks.usable_datapoints_count as datapoints_count');
    }

    public function latestPrice()
    {
        return $this->hasOne(StockPrice::class)->latestOfMany('date');
    }

    public function predictions()
    {
        return $this->hasMany(StockPrediction::class);
    }

    public function watchlists()
    {
        return $this->hasMany(Watchlist::class);
    }

    public function trainedModels()
    {
        return $this->hasMany(TrainedModel::class);
    }
}
