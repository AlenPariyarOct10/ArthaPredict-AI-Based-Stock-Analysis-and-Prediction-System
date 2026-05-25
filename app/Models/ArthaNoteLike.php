<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArthaNoteLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'artha_note_id',
        'user_id',
    ];

    public function note()
    {
        return $this->belongsTo(ArthaNote::class, 'artha_note_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
