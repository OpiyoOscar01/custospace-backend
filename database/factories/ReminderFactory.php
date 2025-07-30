<?php

namespace Database\Factories;

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Reminder Factory
 * 
 * Generates fake reminder data for testing
 */
class ReminderFactory extends Factory
{
    protected $model = Reminder::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'remindable_type' => 'App\\Models\\Task',
            'remindable_id' => $this->faker->numberBetween(1, 100),
            'remind_at' => $this->faker->dateTimeBetween('now', '+30 days'),
            'type' => $this->faker->randomElement(['email', 'in_app', 'sms']),
            'is_sent' => false,
        ];
    }

    /**
     * Indicate that the reminder has been sent.
     */
    public function sent(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_sent' => true,
            ];
        });
    }

    /**
     * Indicate that the reminder is overdue.
     */
    public function overdue(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'remind_at' => $this->faker->dateTimeBetween('-7 days', '-1 day'),
                'is_sent' => false,
            ];
        });
    }

    /**
     * Set reminder type to email.
     */
    public function email(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'email',
            ];
        });
    }

    /**
     * Set reminder type to in-app.
     */
    public function inApp(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'in_app',
            ];
        });
    }

    /**
     * Set reminder type to SMS.
     */
    public function sms(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'sms',
            ];
        });
    }
}
