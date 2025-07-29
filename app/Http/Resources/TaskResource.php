<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'project_id' => $this->project_id,
            'status_id' => $this->status_id,
            'assignee_id' => $this->assignee_id,
            'reporter_id' => $this->reporter_id,
            'parent_id' => $this->parent_id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'type' => $this->type,
            'due_date' => $this->due_date,
            'start_date' => $this->start_date,
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,
            'story_points' => $this->story_points,
            'order' => $this->order,
            'is_recurring' => $this->is_recurring,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Include relationships when they are loaded
            'workspace' => new WorkspaceResource($this->whenLoaded('workspace')),
            'project' => new ProjectResource($this->whenLoaded('project')),
            'status' => new StatusResource($this->whenLoaded('status')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'reporter' => new UserResource($this->whenLoaded('reporter')),
            'parent' => new TaskResource($this->whenLoaded('parent')),
            'children' => TaskResource::collection($this->whenLoaded('children')),
            'subtasks' => SubtaskResource::collection($this->whenLoaded('subtasks')),
            'pipelines' => PipelineResource::collection($this->whenLoaded('pipelines')),
            'dependencies' => TaskResource::collection($this->whenLoaded('dependencies')),
            'dependents' => TaskResource::collection($this->whenLoaded('dependents')),
            'milestones' => MilestoneResource::collection($this->whenLoaded('milestones')),
        ];
    }
}