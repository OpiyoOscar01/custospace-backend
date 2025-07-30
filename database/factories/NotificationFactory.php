<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Notification Factory
 * 
 * Generates fake notification data for testing
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['info', 'warning', 'success', 'error', 'reminder']),
            'title' => $this->faker->sentence(4),
            'message' => $this->faker->paragraph(2),
            'data' => $this->faker->optional()->passthrough([
                'action_url' => $this->faker->url,
                'button_text' => $this->faker->words(2, true),
                'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            ]),
            'notifiable_type' => 'App\\Models\\Task',
            'notifiable_id' => $this->faker->numberBetween(1, 100),
            'is_read' => false,
            'read_at' => null,
        ];
    }

    /**
     * Indicate that the notification has been read.
     */
    public function read(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_read' => true,
                'read_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            ];
        });
    }

    /**
     * Indicate that the notification is unread.
     */
    public function unread(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_read' => false,
                'read_at' => null,
            ];
        });
    }

    /**
     * Set notification type to info.
     */
    public function info(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'info',
                'title' => 'Information: ' . $this->faker->sentence(3),
            ];
        });
    }

    /**
     * Set notification type to warning.
     */
    public function warning(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'warning',
                'title' => 'Warning: ' . $this->faker->sentence(3),
            ];
        });
    }

    /**
     * Set notification type to success.
     */
    public function success(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'success',
                'title' => 'Success: ' . $this->faker->sentence(3),
            ];
        });
    }

    /**
     * Set notification type to error.
     */
    public function error(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'error',
                'title' => 'Error: ' . $this->faker->sentence(3),
            ];
        });
    }
}
