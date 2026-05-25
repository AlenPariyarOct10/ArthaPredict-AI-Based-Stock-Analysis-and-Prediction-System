<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatasetImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'filename',
        'trading_date',
        'total_rows',
        'imported_rows',
        'skipped_rows',
        'error_rows',
        'errors_log',
        'status',
    ];

    protected $casts = [
        'trading_date' => 'date',
        'errors_log' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
