<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $workspace = Workspace::inRandomOrder()->first() ?? Workspace::factory()->create();
        $project = Project::where('workspace_id', $workspace->id)->inRandomOrder()->first() ?? Project::factory()->create(['workspace_id' => $workspace->id]);
        $status = Status::inRandomOrder()->first() ?? Status::factory()->create();
        $reporter = User::inRandomOrder()->first() ?? User::factory()->create();
        $assignee = rand(0, 1) ? User::inRandomOrder()->first() : null;

        return [
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'status_id' => $status->id,
            'assignee_id' => $assignee?->id,
            'reporter_id' => $reporter->id,
            'parent_id' => null, // This would be set in a specific state if needed
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'type' => $this->faker->randomElement(['task', 'bug', 'feature', 'story', 'epic']),
            'due_date' => $this->faker->optional(0.7)->dateTimeBetween('now', '+30 days'),
            'start_date' => $this->faker->optional(0.6)->dateTimeBetween('-10 days', 'now'),
            'estimated_hours' => $this->faker->optional(0.8)->numberBetween(1, 40),
            'actual_hours' => $this->faker->optional(0.5)->numberBetween(1, 50),
            'story_points' => $this->faker->optional(0.6)->numberBetween(1, 13),
            'order' => $this->faker->numberBetween(0, 100),
            'is_recurring' => $this->faker->boolean(10),
            'metadata' => $this->faker->optional(0.3)->randomElement([
                ['sprint' => $this->faker->word()],
                ['custom_field' => $this->faker->word()],
                null
            ]),
        ];
    }

    /**
     * Configure the task as a subtask of another task.
     *
     * @param Task $parentTask
     * @return static
     */
    public function asSubtaskOf(Task $parentTask): static
    {
        return $this->state(function (array $attributes) use ($parentTask) {
            return [
                'workspace_id' => $parentTask->workspace_id,
                'project_id' => $parentTask->project_id,
                'parent_id' => $parentTask->id,
            ];
        });
    }

    /**
     * Configure the task as high priority.
     *
     * @return static
     */
    public function highPriority(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'priority' => 'high',
            ];
        });
    }

    /**
     * Configure the task as a bug.
     *
     * @return static
     */
    public function asBug(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'bug',
            ];
        });
    }

    /**
     * Configure the task as completed.
     *
     * @return static
     */
    public function completed(): static
    {
        // Assuming status_id = 3 is for completed tasks, adjust as needed
        return $this->state(function (array $attributes) {
            return [
                'status_id' => 3,
            ];
        });
    }
}
