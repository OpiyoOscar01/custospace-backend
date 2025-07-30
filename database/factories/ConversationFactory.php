<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Conversation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['direct', 'group', 'channel']),
            'is_private' => $this->faker->boolean(70),
        ];
    }
    
    /**
     * Indicate that the conversation is a direct message.
     *
     * @return $this
     */
    public function direct(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => null,
                'type' => 'direct',
                'is_private' => true,
            ];
        });
    }
    
    /**
     * Indicate that the conversation is a group.
     *
     * @return $this
     */
    public function group(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $this->faker->words(2, true) . ' Group',
                'type' => 'group',
                'is_private' => $this->faker->boolean(80),
            ];
        });
    }
    
    /**
     * Indicate that the conversation is a channel.
     *
     * @return $this
     */
    public function channel(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => '#' . $this->faker->word,
                'type' => 'channel',
                'is_private' => $this->faker->boolean(30),
            ];
        });
    }
}
