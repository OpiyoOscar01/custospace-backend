<?php

namespace App\Http\Resources;

use App\Models\WikiRevision;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wiki Revision Resource - Enhanced version with more data
 */
class WikiRevisionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wiki_id' => $this->wiki_id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'content' => $this->when(
                $request->has('include_content') || $request->routeIs('*.show'),
                $this->content
            ),
            'content_preview' => $this->when(
                !$request->has('include_content') && !$request->routeIs('*.show'),
                \Str::limit(strip_tags($this->content), 150)
            ),
            'summary' => $this->summary,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'avatar' => $this->user->avatar ?? null,
                ];
            }),

            'wiki' => $this->whenLoaded('wiki', function () {
                return [
                    'id' => $this->wiki->id,
                    'title' => $this->wiki->title,
                    'slug' => $this->wiki->slug,
                ];
            }),

            // Computed fields
            'time_ago' => $this->created_at->diffForHumans(),
            'content_length' => strlen($this->content),
            'word_count' => str_word_count(strip_tags($this->content)),
            'is_current' => $this->when(
                $this->relationLoaded('wiki'),
                function () {
                    $latestRevision = $this->wiki->revisions()->latest()->first();
                    return $latestRevision && $latestRevision->id === $this->id;
                }
            ),

            // Changes from previous revision
            'changes_from_previous' => $this->when(
                $request->has('include_changes'),
                function () {
                    $previousRevision = WikiRevision::where('wiki_id', $this->wiki_id)
                        ->where('created_at', '<', $this->created_at)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if (!$previousRevision) {
                        return null;
                    }

                    return [
                        'title_changed' => $previousRevision->title !== $this->title,
                        'content_changed' => $previousRevision->content !== $this->content,
                        'character_diff' => strlen($this->content) - strlen($previousRevision->content),
                        'word_diff' => str_word_count($this->content) - str_word_count($previousRevision->content),
                    ];
                }
            ),
        ];
    }
}
