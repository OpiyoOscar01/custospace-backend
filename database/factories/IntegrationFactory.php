<?php

namespace Database\Factories;

use App\Models\Integration;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Integration Factory
 * 
 * Creates fake integration instances for testing
 */
class IntegrationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Integration::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $types = ['slack', 'github', 'gitlab', 'jira', 'discord', 'teams', 'bitbucket'];
        $type = $this->faker->randomElement($types);

        return [
            'workspace_id' => Workspace::factory(),
            'name' => $this->faker->company . ' ' . ucfirst($type) . ' Integration',
            'type' => $type,
            'configuration' => $this->generateConfiguration($type),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Generate configuration based on integration type
     */
    private function generateConfiguration(string $type): array
    {
        $baseConfig = [
            'api_key' => $this->faker->uuid,
            'webhook_url' => $this->faker->url,
        ];

        return match ($type) {
            'slack' => array_merge($baseConfig, [
                'channel' => '#' . $this->faker->word,
                'bot_token' => 'xoxb-' . $this->faker->regexify('[0-9]{11}-[0-9]{12}-[a-zA-Z0-9]{24}'),
            ]),
            'github' => array_merge($baseConfig, [
                'repository' => $this->faker->userName . '/' . $this->faker->word,
                'access_token' => 'ghp_' . $this->faker->regexify('[a-zA-Z0-9]{36}'),
            ]),
            'gitlab' => array_merge($baseConfig, [
                'project_id' => $this->faker->numberBetween(1, 10000),
                'access_token' => 'glpat-' . $this->faker->regexify('[a-zA-Z0-9]{20}'),
            ]),
            default => $baseConfig,
        };
    }

    /**
     * Indicate that the integration is active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the integration is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create integration of specific type
     */
    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
            'configuration' => $this->generateConfiguration($type),
        ]);
    }
}
