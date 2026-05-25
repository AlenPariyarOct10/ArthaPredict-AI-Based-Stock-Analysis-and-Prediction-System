<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArthaNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'content',
        'image_path',
        'is_pinned',
        'hashtags',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'hashtags' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(ArthaNoteComment::class)
            ->whereNull('parent_id')
            ->latest();
    }

    public function allComments()
    {
        return $this->hasMany(ArthaNoteComment::class);
    }

    public function likes()
    {
        return $this->hasMany(ArthaNoteLike::class);
    }
}
