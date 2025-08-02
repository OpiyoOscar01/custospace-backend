<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Setting Model
 * 
 * Handles application and workspace-specific configuration settings
 */
class Setting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'workspace_id',
        'key',
        'value',
        'type',
    ];

    /**
     * Available setting types
     */
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_JSON = 'json';

    /**
     * Get all available types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_STRING,
            self::TYPE_INTEGER,
            self::TYPE_BOOLEAN,
            self::TYPE_JSON,
        ];
    }

    /**
     * Get the workspace that this setting belongs to
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope for filtering by workspace
     */
    public function scopeForWorkspace($query, ?int $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope for global settings (no workspace)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('workspace_id');
    }

    /**
     * Get typed value based on setting type
     */
    public function getTypedValueAttribute()
    {
        return match ($this->type) {
            self::TYPE_INTEGER => (int) $this->value,
            self::TYPE_BOOLEAN => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_JSON => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Set typed value based on setting type
     */
    public function setTypedValue($value): void
    {
        $this->value = match ($this->type) {
            self::TYPE_JSON => json_encode($value),
            self::TYPE_BOOLEAN => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
