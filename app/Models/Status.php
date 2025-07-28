<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Status Model
 * 
 * Represents a status that can be used in project pipelines.
 * 
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string $slug
 * @property string $color
 * @property string|null $icon
 * @property int $order
 * @property string $type
 * @property bool $is_default
 */
class Status extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'color',
        'icon',
        'order',
        'type',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'order' => 'integer',
        'is_default' => 'boolean',
    ];

    /**
     * Available status types
     */
    public const TYPES = [
        'backlog' => 'Backlog',
        'todo' => 'To Do',
        'in_progress' => 'In Progress',
        'done' => 'Done',
        'cancelled' => 'Cancelled',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from name if not provided
        static::creating(function ($status) {
            if (empty($status->slug)) {
                $status->slug = Str::slug($status->name);
            }
        });

        static::updating(function ($status) {
            if ($status->isDirty('name') && empty($status->slug)) {
                $status->slug = Str::slug($status->name);
            }
        });
    }

    /**
     * Get the workspace that owns the status.
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get pipelines that use this status.
     */
    public function pipelines(): BelongsToMany
    {
        return $this->belongsToMany(Pipeline::class)
                    ->withPivot('order')
                    ->withTimestamps();
    }

    /**
     * Scope statuses by workspace.
     */
    public function scopeByWorkspace($query, int $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope statuses by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope default statuses.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get formatted type attribute.
     */
    public function getFormattedTypeAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
