<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, $default = null)
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, $value, string $type = 'text', string $group = 'general', ?string $label = null, ?string $description = null)
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'label' => $label,
                'description' => $description,
            ]
        );
    }

    /**
     * Get all settings as an array.
     */
    public static function getAll(): array
    {
        return static::pluck('value', 'key')->toArray();
    }

    /**
     * Get the logo URL.
     */
    public static function getLogoUrl(): string
    {
        $logo = static::get('app_logo', 'assets/images/Logo.png');

        // If the logo path is a full URL, return it as is
        if (filter_var($logo, FILTER_VALIDATE_URL)) {
            return $logo;
        }

        // Check if it's a storage logo (uploaded via admin panel)
        if (str_starts_with($logo, 'logos/')) {
            return asset('storage/' . $logo);
        }

        // Otherwise, return the asset URL for default logo
        return asset($logo);
    }

    /**
     * Get the app name.
     */
    public static function getAppName(): string
    {
        return static::get('app_name', config('app.name', 'ArthaPredict'));
    }
}
