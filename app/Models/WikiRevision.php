<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WikiRevision Model - Represents wiki revision history
 * 
 * @property int $id
 * @property int $wiki_id
 * @property int $user_id
 * @property string $title
 * @property string $content
 * @property string|null $summary
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WikiRevision extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wiki_id',
        'user_id',
        'title',
        'content',
        'summary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the wiki that owns this revision.
     */
    public function wiki(): BelongsTo
    {
        return $this->belongsTo(Wiki::class);
    }

    /**
     * Get the user who created this revision.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get revisions by wiki.
     */
    public function scopeByWiki($query, int $wikiId)
    {
        return $query->where('wiki_id', $wikiId);
    }

    /**
     * Scope to get revisions by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
