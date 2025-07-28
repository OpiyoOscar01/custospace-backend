<?php

namespace Database\Factories;

use App\Models\Pipeline;
use App\Models\Project;
use App\Models\Status;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Pipeline Factory
 */
class PipelineFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Pipeline::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);
        
        return [
            'workspace_id' => Workspace::factory(),
            'project_id' => null,
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->optional(0.7)->sentence(),
            'is_default' => false,
        ];
    }
    
    /**
     * Indicate that the pipeline is a default pipeline.
     */
    public function isDefault(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
    
    /**
     * Create a pipeline for a specific project.
     */
    public function forProject(?Project $project = null): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project ? $project->id : Project::factory(),
            'workspace_id' => $project ? $project->workspace_id : $attributes['workspace_id'],
        ]);
    }
    
    /**
     * Create a default pipeline with common statuses.
     */
    public function withDefaultStatuses(): static
    {
        return $this->afterCreating(function (Pipeline $pipeline) {
            $workspace = $pipeline->workspace;
            
            // Create default statuses if needed
            $backlog = Status::where('workspace_id', $workspace->id)
                           ->where('type', 'backlog')
                           ->where('is_default', true)
                           ->first() ?? 
                      Status::factory()->backlog()->isDefault()->create(['workspace_id' => $workspace->id]);
            
            $todo = Status::where('workspace_id', $workspace->id)
                          ->where('type', 'todo')
                          ->where('is_default', true)
                          ->first() ?? 
                     Status::factory()->todo()->isDefault()->create(['workspace_id' => $workspace->id]);
            
            $inProgress = Status::where('workspace_id', $workspace->id)
                               ->where('type', 'in_progress')
                               ->where('is_default', true)
                               ->first() ?? 
                          Status::factory()->inProgress()->isDefault()->create(['workspace_id' => $workspace->id]);
            
            $done = Status::where('workspace_id', $workspace->id)
                          ->where('type', 'done')
                          ->where('is_default', true)
                          ->first() ?? 
                     Status::factory()->done()->isDefault()->create(['workspace_id' => $workspace->id]);
            
            // Attach statuses to pipeline
            $pipeline->statuses()->attach([
                $backlog->id => ['order' => 0],
                $todo->id => ['order' => 1],
                $inProgress->id => ['order' => 2],
                $done->id => ['order' => 3],
            ]);
        });
    }
}
