<?php
// app/Models/Reaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Reaction Model
 * 
 * Handles user reactions (likes, hearts, etc.) to various content
 * 
 * @property int $id
 * @property int $user_id
 * @property string $reactable_type
 * @property int $reactable_id
 * @property string $type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Reaction extends Model
{
    use HasFactory;

    /**
     * Available reaction types.
     */
    public const TYPES = [
        'thumbs_up',
        'thumbs_down',
        'heart',
        'laugh',
        'wow',
        'sad',
        'angry',
        'celebrate',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'reactable_type',
        'reactable_id',
        'type',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who made the reaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reactable model.
     */
    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by reaction type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by reactable type.
     */
    public function scopeByReactableType($query, string $type)
    {
        return $query->where('reactable_type', $type);
    }

    /**
     * Check if the reaction type is valid.
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, self::TYPES);
    }
}