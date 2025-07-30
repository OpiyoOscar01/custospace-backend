<?php

namespace Database\Factories;

use App\Models\Goal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Goal Factory
 * 
 * Creates fake goal instances for testing
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Goal>
 */
class GoalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Goal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $endDate = $this->faker->dateTimeBetween($startDate, '+6 months');
        
        return [
            'workspace_id' => 1, // You might want to create a workspace factory
            'team_id' => $this->faker->optional(0.7)->randomNumber(),
            'owner_id' => 1, // You might want to create a user factory
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional(0.8)->paragraph(),
            'status' => $this->faker->randomElement(Goal::getStatuses()),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'progress' => $this->faker->numberBetween(0, 100),
            'metadata' => $this->faker->optional(0.3)->randomElements([
                'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
                'category' => $this->faker->randomElement(['personal', 'professional', 'team']),
                'tags' => $this->faker->words(3),
            ]),
        ];
    }

    /**
     * Indicate that the goal is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Goal::STATUS_DRAFT,
            'progress' => 0,
        ]);
    }

    /**
     * Indicate that the goal is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Goal::STATUS_ACTIVE,
            'progress' => $this->faker->numberBetween(1, 99),
        ]);
    }

    /**
     * Indicate that the goal is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Goal::STATUS_COMPLETED,
            'progress' => 100,
        ]);
    }

    /**
     * Indicate that the goal is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Goal::STATUS_CANCELLED,
            'progress' => $this->faker->numberBetween(0, 80),
        ]);
    }

    /**
     * Indicate that the goal has no team assignment.
     */
    public function withoutTeam(): static
    {
        return $this->state(fn (array $attributes) => [
            'team_id' => null,
        ]);
    }

    /**
     * Indicate that the goal has specific progress.
     */
    public function withProgress(int $progress): static
    {
        return $this->state(fn (array $attributes) => [
            'progress' => $progress,
        ]);
    }

    /**
     * Indicate that the goal belongs to a specific workspace.
     */
    public function forWorkspace(int $workspaceId): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $workspaceId,
        ]);
    }

    /**
     * Indicate that the goal belongs to a specific team.
     */
    public function forTeam(int $teamId): static
    {
        return $this->state(fn (array $attributes) => [
            'team_id' => $teamId,
        ]);
    }

    /**
     * Indicate that the goal is owned by a specific user.
     */
    public function ownedBy(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $userId,
        ]);
    }
}