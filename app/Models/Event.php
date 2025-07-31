<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Event Model
 * 
 * Represents calendar events within workspaces
 * 
 * @property int $id
 * @property int $workspace_id
 * @property int $created_by_id
 * @property string $title
 * @property string|null $description
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property bool $all_day
 * @property string|null $location
 * @property string $type
 * @property array|null $metadata
 */
class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'workspace_id',
        'created_by_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'all_day',
        'location',
        'type',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'all_day' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Event types enumeration
     */
    public const TYPES = [
        'meeting' => 'Meeting',
        'deadline' => 'Deadline',
        'reminder' => 'Reminder',
        'other' => 'Other',
    ];

    /**
     * Get the workspace that owns the event
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user who created the event
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the event participants
     */
    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    /**
     * Get the users participating in the event
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'event_participants')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    /**
     * Scope events by workspace
     */
    public function scopeByWorkspace($query, int $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope events by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate]);
    }

    /**
     * Scope events by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}