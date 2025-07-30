<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeLog>
 */
class TimeLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TimeLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-30 days', 'now');
        $endedAt = $this->faker->optional(0.8)->dateTimeBetween($startedAt, 'now');
        $duration = $endedAt ? 
            (new \Carbon\Carbon($startedAt))->diffInMinutes(new \Carbon\Carbon($endedAt)) : 
            null;

        return [
            'user_id' => User::factory(),
            'task_id' => Task::factory(),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration' => $duration,
            'description' => $this->faker->optional(0.7)->paragraph(),
            'is_billable' => $this->faker->boolean(0.6),
            'hourly_rate' => $this->faker->optional(0.5)->randomFloat(2, 15, 150),
        ];
    }

    /**
     * Indicate that the time log is billable.
     */
    public function billable(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_billable' => true,
                'hourly_rate' => $this->faker->randomFloat(2, 20, 200),
            ];
        });
    }

    /**
     * Indicate that the time log is currently running.
     */
    public function running(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'started_at' => $this->faker->dateTimeBetween('-8 hours', 'now'),
                'ended_at' => null,
                'duration' => null,
            ];
        });
    }

    /**
     * Indicate that the time log is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = $this->faker->dateTimeBetween('-30 days', '-1 hour');
            $endedAt = $this->faker->dateTimeBetween($startedAt, 'now');
            
            return [
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'duration' => (new \Carbon\Carbon($startedAt))->diffInMinutes(new \Carbon\Carbon($endedAt)),
            ];
        });
    }
}
