<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'project_id',
        'status_id',
        'assignee_id',
        'reporter_id',
        'parent_id',
        'title',
        'description',
        'priority',
        'type',
        'due_date',
        'start_date',
        'estimated_hours',
        'actual_hours',
        'story_points',
        'order',
        'is_recurring',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'datetime',
        'start_date' => 'datetime',
        'is_recurring' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the workspace that owns the task.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the status that owns the task.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Get the user that is assigned to the task.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Get the user that reported the task.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Get the parent task.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    /**
     * Get the child tasks.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    /**
     * Get the subtasks for the task.
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class);
    }

    /**
     * Get the pipelines for the task.
     */
    public function pipelines(): BelongsToMany
    {
        return $this->belongsToMany(Pipeline::class, 'task_pipeline')
            ->withPivot('status_id', 'order')
            ->withTimestamps();
    }

    /**
     * Get the task dependencies.
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_id')
            ->withPivot('type')
            ->withTimestamps();
    }

    /**
     * Get the tasks that depend on this task.
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_id', 'task_id')
            ->withPivot('type')
            ->withTimestamps();
    }

    /**
     * Get the milestones for the task.
     */
    public function milestones(): BelongsToMany
    {
        return $this->belongsToMany(Milestone::class, 'task_milestone')
            ->withTimestamps();
    }

    /**
     * Scope a query to filter tasks by status.
     */
    public function scopeOfStatus($query, $statusId)
    {
        return $query->where('status_id', $statusId);
    }

    /**
     * Scope a query to filter tasks by project.
     */
    public function scopeOfProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope a query to filter tasks by workspace.
     */
    public function scopeOfWorkspace($query, $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope a query to filter tasks by assignee.
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assignee_id', $userId);
    }

    /**
     * Scope a query to filter tasks by priority.
     */
    public function scopeOfPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to filter tasks by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter tasks by due date.
     */
    public function scopeDueBy($query, $date)
    {
        return $query->where('due_date', '<=', $date);
    }

    /**
     * Check if the task is completed.
     */
    public function isCompleted(): bool
    {
        // This would depend on your status logic
        // For example, if status_id = 3 means completed
        return $this->status_id === 3; // Adjust based on your status IDs
    }
}
