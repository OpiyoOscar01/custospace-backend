<?php

namespace Database\Factories;

use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Milestone>
 */
class MilestoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a project or create one
        $project = Project::inRandomOrder()->first() ?? Project::factory()->create();
        
        return [
            'project_id' => $project->id,
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'due_date' => $this->faker->dateTimeBetween('now', '+3 months'),
            'is_completed' => $this->faker->boolean(20), // 20% chance of being completed
            'order' => $this->faker->numberBetween(0, 10),
        ];
    }

    /**
     * Mark the milestone as completed.
     *
     * @return Factory
     */
    public function completed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_completed' => true,
            ];
        });
    }

    /**
     * Set the milestone as upcoming.
     *
     * @return Factory
     */
    public function upcoming(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'due_date' => $this->faker->dateTimeBetween('+1 day', '+2 weeks'),
                'is_completed' => false,
            ];
        });
    }
}
