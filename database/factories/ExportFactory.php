<?php

namespace Database\Factories;

use App\Models\Export;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * Export Factory
 * 
 * Creates fake Export instances for testing
 */
class ExportFactory extends Factory
{
    protected $model = Export::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['csv', 'json', 'excel', 'pdf']),
            'entity' => fake()->randomElement(['tasks', 'projects', 'users']),
            'filters' => fake()->optional()->passthrough([
                ['field' => 'status', 'operator' => '=', 'value' => 'active'],
                ['field' => 'created_at', 'operator' => '>=', 'value' => '2024-01-01']
            ]),
            'file_path' => fake()->optional()->passthrough('exports/' . fake()->uuid() . '.csv'),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed']),
            'expires_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
        ];
    }

    /**
     * Indicate that the export is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'file_path' => null,
        ]);
    }

    /**
     * Indicate that the export is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'file_path' => null,
        ]);
    }

    /**
     * Indicate that the export is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'file_path' => 'exports/' . fake()->uuid() . '.csv',
            'expires_at' => Carbon::now()->addDays(7),
        ]);
    }

    /**
     * Indicate that the export has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'file_path' => null,
        ]);
    }

    /**
     * Indicate that the export has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'file_path' => 'exports/' . fake()->uuid() . '.csv',
            'expires_at' => Carbon::now()->subDays(1),
        ]);
    }
}
