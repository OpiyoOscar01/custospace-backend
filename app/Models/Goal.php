<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Goal Model
 * 
 * Represents a goal entity within a workspace that can be assigned to teams
 * and associated with multiple tasks for tracking progress.
 * 
 * @property int $id
 * @property int $workspace_id
 * @property int|null $team_id
 * @property int $owner_id
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property string|null $start_date
 * @property string|null $end_date
 * @property int $progress
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Goal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'team_id',
        'owner_id',
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'progress',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Available goal statuses
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Get all available statuses
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_ACTIVE,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Get the workspace that owns the goal.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the team associated with the goal.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who owns the goal.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the tasks associated with this goal.
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'goal_tasks')
                    ->withTimestamps();
    }

    /**
     * Check if goal is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if goal is completed
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if goal is cancelled
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Scope to filter goals by workspace
     */
    public function scopeForWorkspace($query, int $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter goals by team
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to filter goals by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter active goals
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}