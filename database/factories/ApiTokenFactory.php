<?php

namespace Database\Factories;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiToken>
 */
class ApiTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'token' => Str::random(80),
            'abilities' => $this->faker->randomElement([
                null,
                ['read'],
                ['write'],
                ['read', 'write'],
                ['admin'],
                ['read', 'write', 'delete'],
            ]),
            'last_used_at' => $this->faker->optional(0.7)->dateTimeBetween('-30 days', 'now'),
            'expires_at' => $this->faker->optional(0.5)->dateTimeBetween('now', '+1 year'),
        ];
    }

    /**
     * Create an expired token
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    /**
     * Create an active token (not expired)
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->optional(0.7)->dateTimeBetween('now', '+1 year'),
        ]);
    }

    /**
     * Create a token with specific abilities
     */
    public function withAbilities(array $abilities): static
    {
        return $this->state(fn (array $attributes) => [
            'abilities' => $abilities,
        ]);
    }

    /**
     * Create a recently used token
     */
    public function recentlyUsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Create a never used token
     */
    public function neverUsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => null,
        ]);
    }
}
