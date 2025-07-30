<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'commentable_type' => $this->faker->randomElement(['App\Models\Task', 'App\Models\Project', 'App\Models\Message']),
            'commentable_id' => $this->faker->numberBetween(1, 100),
            'parent_id' => null,
            'content' => $this->faker->paragraph(),
            'is_internal' => $this->faker->boolean(20),
            'is_edited' => $this->faker->boolean(10),
            'edited_at' => function (array $attributes) {
                return $attributes['is_edited'] ? $this->faker->dateTimeBetween('-1 month', 'now') : null;
            },
        ];
    }
    
    /**
     * Indicate that the comment is a reply.
     *
     * @return $this
     */
    public function asReply(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'parent_id' => Comment::factory(),
            ];
        });
    }
    
    /**
     * Indicate that the comment is internal.
     *
     * @return $this
     */
    public function internal(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_internal' => true,
            ];
        });
    }
}