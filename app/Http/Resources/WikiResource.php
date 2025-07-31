<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wiki Resource - Transforms wiki model for API responses
 */
class WikiResource extends JsonResource
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
            'workspace_id' => $this->workspace_id,
            'parent_id' => $this->parent_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->when(
                $request->routeIs('wikis.show') || $request->has('include_content'),
                $this->content
            ),
            'content_preview' => $this->when(
                !$request->routeIs('wikis.show') && !$request->has('include_content'),
                $this->getContentPreview()
            ),
            'is_published' => $this->is_published,
            'metadata' => $this->metadata,
            'full_path' => $this->getFullPath(),
            'is_root' => $this->isRoot(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'workspace' => $this->whenLoaded('workspace', function () {
                return [
                    'id' => $this->workspace->id,
                    'name' => $this->workspace->name,
                ];
            }),

            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ];
            }),

            'parent' => $this->whenLoaded('parent', function () {
                return [
                    'id' => $this->parent->id,
                    'title' => $this->parent->title,
                    'slug' => $this->parent->slug,
                ];
            }),

            'children' => WikiResource::collection($this->whenLoaded('children')),

            'revisions' => WikiRevisionResource::collection($this->whenLoaded('revisions')),

            'latest_revision' => $this->whenLoaded('latestRevision', function () {
                return new WikiRevisionResource($this->latestRevision);
            }),

            // Computed fields
            'children_count' => $this->when(
                isset($this->children_count),
                $this->children_count
            ),

            'revisions_count' => $this->when(
                isset($this->revisions_count),
                $this->revisions_count
            ),

            // Additional metadata for frontend
            'can_edit' => $this->when(
                \Auth::check(),
                \Auth::user()->can('update', $this->resource)
            ),

            'can_delete' => $this->when(
                \Auth::check(),
                \Auth::user()->can('delete', $this->resource)
            ),

            'collaborative_users' => $this->when(
                !empty($this->metadata['collaborators']),
                collect($this->metadata['collaborators'])->map(function ($collaborator) {
                    return [
                        'user_id' => $collaborator['user_id'],
                        'role' => $collaborator['role'],
                        'assigned_at' => $collaborator['assigned_at'],
                    ];
                })
            ),
        ];
    }

    /**
     * Get content preview (first 200 characters).
     */
    private function getContentPreview(): string
    {
        return \Str::limit(strip_tags($this->content), 200);
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'generated_at' => now()->toISOString(),
            ],
        ];
    }
}
