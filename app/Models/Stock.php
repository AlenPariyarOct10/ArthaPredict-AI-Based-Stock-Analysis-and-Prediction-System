<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = ['symbol', 'name', 'sector', 'exchange', 'is_active'];

    public function prices()
    {
        return $this->hasMany(StockPrice::class);
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
