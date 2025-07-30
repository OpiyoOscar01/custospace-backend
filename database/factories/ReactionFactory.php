<?php
// database/factories/ReactionFactory.php

namespace Database\Factories;

use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Reaction Factory
 * 
 * Generates fake reaction data for testing
 */
class ReactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Reaction::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $reactableTypes = [
            'App\\Models\\Post',
            'App\\Models\\Comment',
            'App\\Models\\Project',
            'App\\Models\\Task',
        ];

        return [
            'user_id' => User::factory(),
            'reactable_type' => $this->faker->randomElement($reactableTypes),
            'reactable_id' => $this->faker->numberBetween(1, 100),
            'type' => $this->faker->randomElement(Reaction::TYPES),
        ];
    }

    /**
     * Create reaction for specific user.
     */
    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Create reaction for specific reactable model.
     */
    public function forReactable(string $type, int $id): static
    {
        return $this->state(fn (array $attributes) => [
            'reactable_type' => $type,
            'reactable_id' => $id,
        ]);
    }

    /**
     * Create reaction with specific type.
     */
    public function withType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Create thumbs up reaction.
     */
    public function thumbsUp(): static
    {
        return $this->withType('thumbs_up');
    }

    /**
     * Create heart reaction.
     */
    public function heart(): static
    {
        return $this->withType('heart');
    }

    /**
     * Create laugh reaction.
     */
    public function laugh(): static
    {
        return $this->withType('laugh');
    }
}
