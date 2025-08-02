<?php

namespace Database\Factories;

use App\Models\Setting;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Setting Factory
 * 
 * Creates fake setting instances for testing
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(Setting::getTypes());
        
        return [
            'workspace_id' => $this->faker->boolean(70) ? Workspace::factory() : null,
            'key' => $this->generateKey(),
            'value' => $this->generateValue($type),
            'type' => $type,
        ];
    }

    /**
     * State for global settings
     */
    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => null,
            'key' => $this->faker->randomElement([
                'app.name',
                'app.version',
                'app.timezone',
                'system.maintenance_mode',
                'mail.driver',
                'cache.default',
                'queue.default',
            ]),
        ]);
    }

    /**
     * State for workspace-specific settings
     */
    public function workspace(?int $workspaceId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $workspaceId ?? Workspace::factory(),
            'key' => $this->faker->randomElement([
                'theme.primary_color',
                'theme.secondary_color',
                'notifications.email_enabled',
                'notifications.slack_webhook',
                'billing.currency',
                'feature.analytics_enabled',
                'feature.reports_enabled',
            ]),
        ]);
    }

    /**
     * State for string type settings
     */
    public function string(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Setting::TYPE_STRING,
            'value' => $this->faker->sentence(),
        ]);
    }

    /**
     * State for integer type settings
     */
    public function integer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Setting::TYPE_INTEGER,
            'value' => (string) $this->faker->numberBetween(1, 1000),
        ]);
    }

    /**
     * State for boolean type settings
     */
    public function boolean(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Setting::TYPE_BOOLEAN,
            'value' => $this->faker->boolean() ? '1' : '0',
        ]);
    }

    /**
     * State for JSON type settings
     */
    public function json(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Setting::TYPE_JSON,
            'value' => json_encode([
                'enabled' => $this->faker->boolean(),
                'options' => [
                    'timeout' => $this->faker->numberBetween(30, 300),
                    'retries' => $this->faker->numberBetween(1, 5),
                ],
                'features' => $this->faker->words(3),
            ]),
        ]);
    }

    /**
     * Generate a setting key
     */
    private function generateKey(): string
    {
        $categories = ['app', 'system', 'mail', 'cache', 'queue', 'theme', 'notifications', 'billing', 'feature'];
        $category = $this->faker->randomElement($categories);
        $key = $this->faker->randomElement([
            'enabled', 'disabled', 'timeout', 'retries', 'limit', 'color', 'size', 'format', 'driver', 'host', 'port'
        ]);
        
        return "{$category}.{$key}";
    }

    /**
     * Generate value based on type
     */
    private function generateValue(string $type): string
    {
        return match ($type) {
            Setting::TYPE_STRING => $this->faker->sentence(),
            Setting::TYPE_INTEGER => (string) $this->faker->numberBetween(1, 1000),
            Setting::TYPE_BOOLEAN => $this->faker->boolean() ? '1' : '0',
            Setting::TYPE_JSON => json_encode([
                'enabled' => $this->faker->boolean(),
                'value' => $this->faker->word(),
                'count' => $this->faker->numberBetween(1, 100),
            ]),
        };
    }
}
