<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\WikiRevision;

/**
 * Wiki Model - Represents knowledge base articles
 * 
 * @property int $id
 * @property int $workspace_id
 * @property int $created_by_id
 * @property int|null $parent_id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property bool $is_published
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Wiki extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'created_by_id',
        'parent_id',
        'title',
        'slug',
        'content',
        'is_published',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_published' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from title if not provided
        static::creating(function ($wiki) {
            if (empty($wiki->slug)) {
                $wiki->slug = Str::slug($wiki->title);
            }
        });

        static::updating(function ($wiki) {
            if ($wiki->isDirty('title') && empty($wiki->slug)) {
                $wiki->slug = Str::slug($wiki->title);
            }
        });
    }

    /**
     * Get the workspace that owns the wiki.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user who created the wiki.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the parent wiki.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Wiki::class, 'parent_id');
    }

    /**
     * Get the child wikis.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Wiki::class, 'parent_id');
    }

    /**
     * Get all revisions for this wiki.
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(WikiRevision::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the latest revision.
     */
    public function latestRevision(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WikiRevision::class)->latestOfMany();
    }

    /**
     * Scope to get only published wikis.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to get wikis by workspace.
     */
    public function scopeByWorkspace($query, int $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to get root wikis (no parent).
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Check if the wiki is a root wiki.
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Get the full path of the wiki including parents.
     */
    public function getFullPath(): string
    {
        $path = collect([$this->title]);
        $parent = $this->parent;

        while ($parent) {
            $path->prepend($parent->title);
            $parent = $parent->parent;
        }

        return $path->implode(' > ');
    }
}
