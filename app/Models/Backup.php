<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Backup Model
 * 
 * Represents backup records in the system with workspace associations
 * and status tracking for backup operations.
 */
class Backup extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'name',
        'type',
        'path',
        'disk',
        'size',
        'status',
        'started_at',
        'completed_at',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'size' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Status constants for better code maintainability
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Backup type constants
     */
    public const TYPE_FULL = 'full';
    public const TYPE_INCREMENTAL = 'incremental';
    public const TYPE_TABLES = 'tables';

    /**
     * Get all available statuses
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
        ];
    }

    /**
     * Get all available backup types
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_FULL,
            self::TYPE_INCREMENTAL,
            self::TYPE_TABLES,
        ];
    }

    /**
     * Get the workspace that owns the backup.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope a query to only include completed backups.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope a query to only include failed backups.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope a query to only include backups of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if backup is completed
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if backup is in progress
     *
     * @return bool
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if backup has failed
     *
     * @return bool
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
