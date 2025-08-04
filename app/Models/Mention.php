<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Mention extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'mentionable_type',
        'mentionable_id',
        'mentioned_by_id',
        'is_read',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Get the user who was mentioned.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who made the mention.
     */
    public function mentionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_by_id');
    }

    /**
     * Get the mentionable model (Comment or Message).
     */
    public function mentionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include unread mentions.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
