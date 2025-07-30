<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
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
        'is_private',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_private' => 'boolean',
    ];

    /**
     * Get the workspace that owns the conversation.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the users that belong to the conversation.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
                    ->withPivot(['role', 'joined_at', 'last_read_at'])
                    ->withTimestamps();
    }

    /**
     * Get the messages for the conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Check if the conversation is a direct conversation.
     */
    public function isDirect(): bool
    {
        return $this->type === 'direct';
    }

    /**
     * Check if the conversation is a group conversation.
     */
    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    /**
     * Check if the conversation is a channel.
     */
    public function isChannel(): bool
    {
        return $this->type === 'channel';
    }

    /**
     * Get the last message of this conversation.
     */
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }
}
