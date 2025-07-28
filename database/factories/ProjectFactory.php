<?php
// database/factories/ProjectFactory.php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Project Factory
 * 
 * Generates fake project data for testing and seeding.
 */
class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Project::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->words(3, true);
        $startDate = $this->faker->optional(0.7)->dateTimeBetween('-6 months', '+1 month');
        $endDate = $startDate ? $this->faker->optional(0.8)->dateTimeBetween($startDate, '+1 year') : null;

        return [
            'workspace_id' => Workspace::factory(),
            'team_id' => $this->faker->optional(0.6)->randomElement(Team::pluck('id')->toArray()) ?: Team::factory(),
            'owner_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->randomNumber(4),
            'description' => $this->faker->optional(0.8)->paragraph(),
            'color' => $this->faker->randomElement([
                '#10B981', '#3B82F6', '#8B5CF6', '#EF4444', 
                '#F59E0B', '#06B6D4', '#84CC16', '#EC4899'
            ]),
            'status' => $this->faker->randomElement(array_keys(Project::STATUSES)),
            'priority' => $this->faker->randomElement(array_keys(Project::PRIORITIES)),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'budget' => $this->faker->optional(0.6)->randomFloat(2, 1000, 100000),
            'progress' => $this->faker->numberBetween(0, 100),
            'is_template' => $this->faker->boolean(10), // 10% chance of being template
            'metadata' => $this->faker->optional(0.3)->randomElements([
                'client' => $this->faker->company(),
                'category' => $this->faker->randomElement(['web', 'mobile', 'desktop', 'api']),
                'tags' => $this->faker->words(3),
                'external_id' => $this->faker->uuid(),
            ]),
        ];
    }

    /**
     * Indicate that the project is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'progress' => $this->faker->numberBetween(1, 90),
        ]);
    }

    /**
     * Indicate that the project is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress' => 100,
            'end_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ]);
    }

    /**
     * Indicate that the project is a template.
     */
    public function template(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_template' => true,
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the project has high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * Indicate that the project is urgent.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
            'status' => 'active',
        ]);
    }

    /**
     * Configure the model factory with relationships.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Project $project) {
            // Attach owner as project user with owner role
            $project->users()->attach($project->owner_id, [
                'role' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Optionally attach additional random users
            if ($this->faker->boolean(60)) { // 60% chance
                $additionalUsers = User::inRandomOrder()
                    ->limit($this->faker->numberBetween(1, 3))
                    ->pluck('id')
                    ->toArray();

                foreach ($additionalUsers as $userId) {
                    if ($userId !== $project->owner_id) {
                        $project->users()->attach($userId, [
                            'role' => $this->faker->randomElement(['manager', 'contributor', 'viewer']),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });
    }
}
