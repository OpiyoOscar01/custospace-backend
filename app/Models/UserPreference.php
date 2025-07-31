<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserPreference Model
 * 
 * Stores user-specific preferences as key-value pairs.
 * Provides methods for preference management and retrieval.
 */
class UserPreference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'key',
        'value',
    ];

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get preference value with optional default.
     */
    public function getValue($default = null)
    {
        return $this->value ?? $default;
    }

    /**
     * Get JSON decoded value if it's JSON, otherwise return raw value.
     */
    public function getDecodedValue($default = null)
    {
        $decoded = json_decode($this->value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : ($this->value ?? $default);
    }

    /**
     * Set preference value (automatically JSON encode arrays/objects).
     */
    public function setValue($value): bool
    {
        $encodedValue = is_array($value) || is_object($value) ? json_encode($value) : $value;
        return $this->update(['value' => $encodedValue]);
    }

    /**
     * Scope a query to only include preferences for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include preferences with a specific key.
     */
    public function scopeWithKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Get all preferences for a user as an associative array.
     */
    public static function getAllForUser(int $userId): array
    {
        return static::forUser($userId)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Set a preference for a user (create or update).
     */
    public static function setForUser(int $userId, string $key, $value): UserPreference
    {
        $encodedValue = is_array($value) || is_object($value) ? json_encode($value) : $value;
        
        return static::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $encodedValue]
        );
    }

    /**
     * Get a specific preference for a user.
     */
    public static function getForUser(int $userId, string $key, $default = null)
    {
        $preference = static::forUser($userId)->withKey($key)->first();
        return $preference ? $preference->getDecodedValue($default) : $default;
    }
}