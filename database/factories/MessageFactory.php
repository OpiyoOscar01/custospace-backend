<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'user_id' => User::factory(),
            'content' => $this->faker->paragraph(),
            'type' => 'text',
            'metadata' => null,
            'is_edited' => $this->faker->boolean(10),
            'edited_at' => function (array $attributes) {
                return $attributes['is_edited'] ? $this->faker->dateTimeBetween('-1 month', 'now') : null;
            },
        ];
    }
    
    /**
     * Indicate that the message is a system message.
     *
     * @return $this
     */
    public function system(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'system',
                'content' => $this->faker->randomElement([
                    'User has joined the conversation',
                    'User has left the conversation',
                    'Conversation created',
                    'User was added to the conversation',
                ]),
            ];
        });
    }
    
    /**
     * Indicate that the message is a file message.
     *
     * @return $this
     */
    public function file(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'file',
                'content' => $this->faker->word() . '.pdf',
                'metadata' => [
                    'file_name' => $this->faker->word() . '.pdf',
                    'file_size' => $this->faker->numberBetween(1000, 5000000),
                    'file_type' => 'application/pdf',
                    'url' => $this->faker->url(),
                ],
            ];
        });
    }
    
    /**
     * Indicate that the message is an image message.
     *
     * @return $this
     */
    public function image(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'image',
                'content' => '',
                'metadata' => [
                    'file_name' => $this->faker->word() . '.jpg',
                    'file_size' => $this->faker->numberBetween(50000, 2000000),
                    'file_type' => 'image/jpeg',
                    'url' => $this->faker->imageUrl(),
                    'width' => $this->faker->numberBetween(400, 1200),
                    'height' => $this->faker->numberBetween(400, 1200),
                ],
            ];
        });
    }
}
