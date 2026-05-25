<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArthaNoteComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'artha_note_id',
        'user_id',
        'parent_id',
        'body',
    ];

    public function note()
    {
        return $this->belongsTo(ArthaNote::class, 'artha_note_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }
}
