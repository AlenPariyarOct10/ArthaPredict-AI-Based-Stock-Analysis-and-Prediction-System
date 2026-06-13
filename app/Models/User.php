<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_admin', 'profile_image'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the URL to the user's profile image.
     */
    public function getProfileImageUrlAttribute(): string
    {
        return $this->profile_image
            ? asset('storage/' . $this->profile_image)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=0d9488&background=f0fdf4&bold=true';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    public function watchlists()
    {
        return $this->hasMany(Watchlist::class);
    }
    
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function arthaNotes()
    {
        return $this->hasMany(ArthaNote::class);
    }

    public function arthaNoteComments()
    {
        return $this->hasMany(ArthaNoteComment::class);
    }

    public function arthaNoteLikes()
    {
        return $this->hasMany(ArthaNoteLike::class);
    }
}
