<?php

namespace Database\Factories;

use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subtask>
 */
class SubtaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a task or create one
        $task = Task::inRandomOrder()->first() ?? Task::factory()->create();
        
        return [
            'task_id' => $task->id,
            'title' => $this->faker->sentence(),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'is_completed' => $this->faker->boolean(20), // 20% chance of being completed
            'order' => $this->faker->numberBetween(0, 10),
        ];
    }

    /**
     * Mark the subtask as completed.
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
}
