<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'plan_id' => Plan::factory(),
            'stripe_id' => 'sub_' . $this->faker->unique()->regexify('[A-Za-z0-9]{14}'),
            'stripe_status' => $this->faker->randomElement(['active', 'inactive', 'trialing', 'canceled']),
            'stripe_price' => 'price_' . $this->faker->regexify('[A-Za-z0-9]{14}'),
            'quantity' => $this->faker->numberBetween(1, 10),
            'trial_ends_at' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'ends_at' => $this->faker->optional()->dateTimeBetween('+30 days', '+1 year'),
        ];
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_status' => 'active',
            'ends_at' => null,
        ]);
    }

    /**
     * Indicate that the subscription is on trial.
     */
    public function onTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_status' => 'trialing',
            'trial_ends_at' => $this->faker->dateTimeBetween('now', '+14 days'),
        ]);
    }

    /**
     * Indicate that the subscription is canceled.
     */
    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_status' => 'canceled',
            'ends_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }
}
