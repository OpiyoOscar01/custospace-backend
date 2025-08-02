<?php

namespace Database\Factories;

use App\Models\Import;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Import Factory
 * 
 * Creates fake Import instances for testing
 */
class ImportFactory extends Factory
{
    protected $model = Import::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalRows = fake()->numberBetween(10, 1000);
        $processedRows = fake()->numberBetween(0, $totalRows);
        $successfulRows = fake()->numberBetween(0, $processedRows);
        $failedRows = $processedRows - $successfulRows;

        return [
            'workspace_id' => Workspace::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['csv', 'json', 'excel']),
            'entity' => fake()->randomElement(['tasks', 'projects', 'users']),
            'file_path' => 'imports/' . fake()->uuid() . '.csv',
            'total_rows' => $totalRows,
            'processed_rows' => $processedRows,
            'successful_rows' => $successfulRows,
            'failed_rows' => $failedRows,
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed']),
            'errors' => fake()->optional()->passthrough([
                'validation_errors' => fake()->words(3),
                'line_errors' => fake()->numberBetween(1, 10)
            ]),
        ];
    }

    /**
     * Indicate that the import is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'errors' => null,
        ]);
    }

    /**
     * Indicate that the import is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * Indicate that the import is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'processed_rows' => $attributes['total_rows'],
            'successful_rows' => $attributes['total_rows'],
            'failed_rows' => 0,
        ]);
    }

    /**
     * Indicate that the import has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'errors' => ['error' => 'Import processing failed'],
        ]);
    }
}
