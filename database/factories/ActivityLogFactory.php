<?php
// database/factories/ActivityLogFactory.php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Activity Log Factory
 * 
 * Generates fake activity log data for testing
 */
class ActivityLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $actions = [
            'created',
            'updated',
            'deleted',
            'viewed',
            'downloaded',
            'uploaded',
            'shared',
            'commented',
            'liked',
            'assigned'
        ];

        $subjectTypes = [
            'App\\Models\\Project',
            'App\\Models\\Task',
            'App\\Models\\Document',
            'App\\Models\\Comment',
            'App\\Models\\User',
        ];

        $action = $this->faker->randomElement($actions);
        $subjectType = $this->faker->randomElement($subjectTypes);

        return [
            'user_id' => User::factory(),
            'workspace_id' => Workspace::factory(),
            'action' => $action,
            'description' => $this->generateDescription($action),
            'subject_type' => $subjectType,
            'subject_id' => $this->faker->numberBetween(1, 100),
            'properties' => $this->generateProperties($action),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Generate a description based on action.
     */
    private function generateDescription(string $action): string
    {
        return match($action) {
            'created' => 'Created a new ' . $this->faker->randomElement(['project', 'task', 'document']),
            'updated' => 'Updated ' . $this->faker->randomElement(['project details', 'task status', 'document content']),
            'deleted' => 'Deleted a ' . $this->faker->randomElement(['project', 'task', 'document']),
            'viewed' => 'Viewed ' . $this->faker->randomElement(['project dashboard', 'task details', 'document']),
            'downloaded' => 'Downloaded ' . $this->faker->randomElement(['report', 'document', 'attachment']),
            'uploaded' => 'Uploaded a new ' . $this->faker->randomElement(['document', 'image', 'file']),
            'shared' => 'Shared ' . $this->faker->randomElement(['project', 'document', 'link']),
            'commented' => 'Added a comment to ' . $this->faker->randomElement(['project', 'task', 'document']),
            'liked' => 'Liked a ' . $this->faker->randomElement(['comment', 'post', 'update']),
            'assigned' => 'Assigned a task to ' . $this->faker->name(),
            default => ucfirst($action) . ' performed',
        };
    }

    /**
     * Generate properties based on action.
     */
    private function generateProperties(string $action): ?array
    {
        return match($action) {
            'created' => [
                'entity_name' => $this->faker->words(3, true),
                'category' => $this->faker->randomElement(['work', 'personal', 'urgent']),
            ],
            'updated' => [
                'fields_changed' => $this->faker->randomElements(['name', 'description', 'status', 'priority'], 2),
                'old_status' => $this->faker->randomElement(['pending', 'in_progress', 'completed']),
                'new_status' => $this->faker->randomElement(['in_progress', 'completed', 'cancelled']),
            ],
            'shared' => [
                'shared_with' => $this->faker->randomElements(['team', 'client', 'public'], 1),
                'permission_level' => $this->faker->randomElement(['view', 'edit', 'admin']),
            ],
            default => null,
        };
    }

    /**
     * Create activity log for a specific workspace.
     */
    public function forWorkspace(int $workspaceId): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $workspaceId,
        ]);
    }

    /**
     * Create activity log for a specific user.
     */
    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Create activity log with specific action.
     */
    public function withAction(string $action): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => $action,
            'description' => $this->generateDescription($action),
            'properties' => $this->generateProperties($action),
        ]);
    }
}
