<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Integration Model
 * 
 * Represents third-party integrations within workspaces
 * Supports various integration types like Slack, GitHub, GitLab, etc.
 */
class Integration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'workspace_id',
        'name',
        'type',
        'configuration',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Integration belongs to a workspace
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope to filter active integrations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by integration type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if integration is of specific type
     */
    public function isType(string $type): bool
    {
        return $this->type === $type;
    }
}
