<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Reminder Model
 * 
 * Manages reminders for various entities in the system
 */
class Reminder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'remindable_type',
        'remindable_id',
        'remind_at',
        'type',
        'is_sent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'remind_at' => 'datetime',
        'is_sent' => 'boolean',
    ];

    /**
     * Reminder types enum values
     */
    public const TYPES = [
        'email' => 'email',
        'in_app' => 'in_app',
        'sms' => 'sms',
    ];

    /**
     * Get the user that owns the reminder
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the remindable entity (polymorphic relationship)
     */
    public function remindable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to get pending reminders
     */
    public function scopePending($query)
    {
        return $query->where('is_sent', false);
    }

    /**
     * Scope to get sent reminders
     */
    public function scopeSent($query)
    {
        return $query->where('is_sent', true);
    }

    /**
     * Scope to get reminders due before a specific time
     */
    public function scopeDueBefore($query, $datetime)
    {
        return $query->where('remind_at', '<=', $datetime);
    }

    /**
     * Mark reminder as sent
     */
    public function markAsSent(): bool
    {
        return $this->update(['is_sent' => true]);
    }
}
