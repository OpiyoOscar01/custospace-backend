<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Include relationships if they are loaded
            'workspace' => new WorkspaceResource($this->whenLoaded('workspace')),
            'users' => UserResource::collection($this->whenLoaded('users')),
            
            // Add relevant links for HATEOAS
            'links' => [
                'self' => route('api.teams.show', $this->id),
                'workspace' => route('api.workspaces.show', $this->workspace_id),
            ],
        ];
    }
}
