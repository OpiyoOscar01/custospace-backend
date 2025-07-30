<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Mention;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mention>
 */
class MentionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Mention::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mentionableType = $this->faker->randomElement([
            Comment::class,
            Message::class,
        ]);
        
        $mentionable = $mentionableType === Comment::class
            ? Comment::factory()->create()
            : Message::factory()->create();
        
        return [
            'user_id' => User::factory(),
            'mentionable_type' => $mentionableType,
            'mentionable_id' => $mentionable->id,
            'mentioned_by_id' => User::factory(),
            'is_read' => $this->faker->boolean(30),
        ];
    }
    
    /**
     * Indicate that the mention is for a comment.
     *
     * @return $this
     */
    public function forComment(): static
    {
        return $this->state(function (array $attributes) {
            $comment = Comment::factory()->create();
            
            return [
                'mentionable_type' => Comment::class,
                'mentionable_id' => $comment->id,
            ];
        });
    }
    
    /**
     * Indicate that the mention is for a message.
     *
     * @return $this
     */
    public function forMessage(): static
    {
        return $this->state(function (array $attributes) {
            $message = Message::factory()->create();
            
            return [
                'mentionable_type' => Message::class,
                'mentionable_id' => $message->id,
            ];
        });
    }
    
    /**
     * Indicate that the mention is read.
     *
     * @return $this
     */
    public function read(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_read' => true,
            ];
        });
    }
    
    /**
     * Indicate that the mention is unread.
     *
     * @return $this
     */
    public function unread(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_read' => false,
            ];
        });
    }
}
