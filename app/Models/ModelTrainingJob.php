<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelTrainingJob extends Model
{
    use HasFactory;

    protected $fillable = ['stock_id', 'user_id', 'status', 'error_message', 'total_rows', 'processed_rows', 'current_stage', 'started_at', 'completed_at', 'meta'];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
