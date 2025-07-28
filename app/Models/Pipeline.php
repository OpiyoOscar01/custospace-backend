<?php
// app/Models/Pipeline.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Pipeline Model
 * 
 * Represents a workflow pipeline that contains ordered statuses for project tasks.
 * 
 * @property int $id
 * @property int $workspace_id
 * @property int|null $project_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_default
 */
class Pipeline extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'workspace_id',
        'project_id',
        'name',
        'slug',
        'description',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from name if not provided
        static::creating(function ($pipeline) {
            if (empty($pipeline->slug)) {
                $pipeline->slug = Str::slug($pipeline->name);
            }
        });

        static::updating(function ($pipeline) {
            if ($pipeline->isDirty('name') && empty($pipeline->slug)) {
                $pipeline->slug = Str::slug($pipeline->name);
            }
        });
    }

    /**
     * Get the workspace that owns the pipeline.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the project that owns the pipeline.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get statuses in this pipeline with their order.
     */
    public function statuses(): BelongsToMany
    {
        return $this->belongsToMany(Status::class)
                    ->withPivot('order')
                    ->withTimestamps()
                    ->orderBy('pipeline_status.order');
    }

    /**
     * Scope pipelines by workspace.
     */
    public function scopeByWorkspace($query, int $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope pipelines by project.
     */
    public function scopeByProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope default pipelines.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
