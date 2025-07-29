<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Milestone extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'due_date',
        'is_completed',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',
        'is_completed' => 'boolean',
    ];

    /**
     * Get the project that owns the milestone.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the tasks for the milestone.
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_milestone')
            ->withTimestamps();
    }

    /**
     * Scope a query to filter by completion status.
     */
    public function scopeCompleted($query, $isCompleted = true)
    {
        return $query->where('is_completed', $isCompleted);
    }

    /**
     * Scope a query to order by the 'order' field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Scope a query to filter by project.
     */
    public function scopeOfProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope a query to filter by due date.
     */
    public function scopeDueBefore($query, $date)
    {
        return $query->where('due_date', '<=', $date);
    }
    
    /**
     * Scope a query to filter by upcoming milestones.
     */
    public function scopeUpcoming($query, $days = 30)
    {
        $endDate = now()->addDays($days);
        return $query->where('due_date', '>=', now())
                    ->where('due_date', '<=', $endDate)
                    ->where('is_completed', false);
    }
}
