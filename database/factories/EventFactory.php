<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Event Factory
 * 
 * Creates fake event instances for testing
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('+1 day', '+1 month');
        $endDate = (clone $startDate)->modify('+' . $this->faker->numberBetween(1, 4) . ' hours');

        return [
            'workspace_id' => Workspace::factory(),
            'created_by_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'all_day' => $this->faker->boolean(20), // 20% chance of being all day
            'location' => $this->faker->optional(0.6)->address(),
            'type' => $this->faker->randomElement(['meeting', 'deadline', 'reminder', 'other']),
            'metadata' => $this->faker->optional(0.3)->randomElement([
                ['priority' => 'high'],
                ['recurring' => true, 'frequency' => 'weekly'],
                ['online' => true, 'meeting_url' => 'https://meet.example.com/room123'],
            ]),
        ];
    }

    /**
     * Create a meeting event
     */
    public function meeting(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'meeting',
            'location' => $this->faker->randomElement([
                'Conference Room A',
                'Meeting Room 1',
                'Board Room',
                null, // Online meeting
            ]),
            'metadata' => [
                'online' => $this->faker->boolean(70),
                'meeting_url' => $this->faker->boolean(70) ? 'https://meet.example.com/room' . $this->faker->randomNumber(6) : null,
            ],
        ]);
    }

    /**
     * Create a deadline event
     */
    public function deadline(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deadline',
            'all_day' => true,
            'location' => null,
            'metadata' => [
                'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
                'project' => $this->faker->words(2, true),
            ],
        ]);
    }

    /**
     * Create a reminder event
     */
    public function reminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'reminder',
            'description' => 'Reminder: ' . $this->faker->sentence(),
            'metadata' => [
                'reminder_minutes' => $this->faker->randomElement([15, 30, 60, 120]),
            ],
        ]);
    }

    /**
     * Create an all-day event
     */
    public function allDay(): static
    {
        $date = $this->faker->dateTimeBetween('+1 day', '+1 month');
        
        return $this->state(fn (array $attributes) => [
            'all_day' => true,
            'start_date' => $date->setTime(0, 0, 0),
            'end_date' => (clone $date)->setTime(23, 59, 59),
        ]);
    }

    /**
     * Create a recurring event
     */
    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => [
                'recurring' => true,
                'frequency' => $this->faker->randomElement(['daily', 'weekly', 'monthly']),
                'end_recurrence' => $this->faker->dateTimeBetween('+2 months', '+1 year')->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Create a cancelled event
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => [
                'cancelled' => true,
                'cancelled_at' => now()->toISOString(),
                'cancellation_reason' => $this->faker->sentence(),
            ],
        ]);
    }

    /**
     * Create an online event
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => null,
            'metadata' => [
                'online' => true,
                'meeting_url' => 'https://meet.example.com/room' . $this->faker->randomNumber(6),
                'meeting_password' => $this->faker->optional(0.5)->lexify('??????'),
            ],
        ]);
    }
}