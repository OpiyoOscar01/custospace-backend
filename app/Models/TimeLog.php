<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Class TimeLog
 * 
 * Represents a time tracking entry for a specific task by a user
 * 
 * @property int $id
 * @property int $user_id
 * @property int $task_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property int|null $duration Duration in minutes
 * @property string|null $description
 * @property bool $is_billable
 * @property float|null $hourly_rate
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read Task $task
 */
class TimeLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'task_id',
        'started_at',
        'ended_at',
        'duration',
        'description',
        'is_billable',
        'hourly_rate',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_billable' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'duration' => 'integer',
    ];

    /**
     * Get the user that owns the time log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the task that this time log belongs to.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Check if the time log is currently running (no end time).
     */
    public function isRunning(): bool
    {
        return is_null($this->ended_at);
    }

    /**
     * Calculate and update the duration based on start and end times.
     */
    public function calculateDuration(): void
    {
        if ($this->started_at && $this->ended_at) {
            $this->duration = $this->started_at->diffInMinutes($this->ended_at);
        }
    }

    /**
     * Calculate total earnings for this time log.
     */
    public function getTotalEarnings(): float
    {
        if (!$this->is_billable || !$this->hourly_rate || !$this->duration) {
            return 0.0;
        }

        return ($this->duration / 60) * $this->hourly_rate;
    }

    /**
     * Scope to get billable time logs only.
     */
    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    /**
     * Scope to get running time logs (no end time).
     */
    public function scopeRunning($query)
    {
        return $query->whereNull('ended_at');
    }

    /**
     * Scope to get completed time logs.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('ended_at');
    }
}
