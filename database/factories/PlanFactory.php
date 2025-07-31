<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Plan Factory
 * 
 * Creates fake plan instances for testing
 */
class PlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Plan::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement(['Starter', 'Professional', 'Enterprise', 'Premium', 'Basic']);
        $billingCycle = $this->faker->randomElement(['monthly', 'yearly']);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name . '-' . $billingCycle),
            'description' => $this->faker->paragraph,
            'price' => $this->faker->randomElement([0, 9.99, 19.99, 49.99, 99.99, 199.99]),
            'billing_cycle' => $billingCycle,
            'max_users' => $this->faker->optional()->numberBetween(1, 100),
            'max_projects' => $this->faker->optional()->numberBetween(5, 1000),
            'max_storage_gb' => $this->faker->optional()->numberBetween(10, 1000),
            'features' => $this->generateFeatures(),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'is_popular' => $this->faker->boolean(20), // 20% chance of being popular
        ];
    }

    /**
     * Generate random features array
     */
    private function generateFeatures(): array
    {
        $allFeatures = [
            'API Access',
            'Priority Support',
            'Advanced Analytics',
            'Custom Integrations',
            'Single Sign-On',
            'Advanced Security',
            'Custom Branding',
            'Bulk Operations',
            'Advanced Reporting',
            'Real-time Notifications',
            'Data Export',
            'Team Collaboration',
            'Advanced Permissions',
            'Audit Logs',
            'Custom Fields',
        ];

        return $this->faker->randomElements($allFeatures, $this->faker->numberBetween(3, 8));
    }

    /**
     * Indicate that the plan is active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the plan is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the plan is popular
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_popular' => true,
        ]);
    }

    /**
     * Create free plan
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Free',
            'slug' => 'free',
            'price' => 0.00,
            'max_users' => 3,
            'max_projects' => 5,
            'max_storage_gb' => 1,
        ]);
    }

    /**
     * Create monthly plan
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_cycle' => 'monthly',
        ]);
    }

    /**
     * Create yearly plan
     */
    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_cycle' => 'yearly',
        ]);
    }
}
