<?php

namespace Database\Factories;

use App\Models\Backup;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Backup Factory
 * 
 * Creates fake backup instances for testing
 */
class BackupFactory extends Factory
{
    protected $model = Backup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = Backup::getStatuses();
        $types = Backup::getTypes();
        $status = $this->faker->randomElement($statuses);

        return [
            'workspace_id' => Workspace::factory(),
            'name' => $this->faker->words(3, true) . '_backup',
            'type' => $this->faker->randomElement($types),
            'path' => '/backups/' . $this->faker->uuid() . '.sql',
            'disk' => $this->faker->randomElement(['s3', 'local']),
            'size' => $this->faker->numberBetween(1000000, 5000000000), // 1MB to 5GB
            'status' => $status,
            'started_at' => $status !== Backup::STATUS_PENDING ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
            'completed_at' => in_array($status, [Backup::STATUS_COMPLETED, Backup::STATUS_FAILED]) ? 
                $this->faker->dateTimeBetween('-1 week', 'now') : null,
            'error_message' => $status === Backup::STATUS_FAILED ? $this->faker->sentence() : null,
        ];
    }

    /**
     * Indicate that the backup is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Backup::STATUS_PENDING,
            'started_at' => null,
            'completed_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the backup is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Backup::STATUS_IN_PROGRESS,
            'started_at' => $this->faker->dateTimeBetween('-2 hours', 'now'),
            'completed_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the backup is completed.
     */
    public function completed(): static
    {
        $startedAt = $this->faker->dateTimeBetween('-1 week', '-1 hour');
        
        return $this->state(fn (array $attributes) => [
            'status' => Backup::STATUS_COMPLETED,
            'started_at' => $startedAt,
            'completed_at' => $this->faker->dateTimeBetween($startedAt, 'now'),
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the backup has failed.
     */
    public function failed(): static
    {
        $startedAt = $this->faker->dateTimeBetween('-1 week', '-1 hour');
        
        return $this->state(fn (array $attributes) => [
            'status' => Backup::STATUS_FAILED,
            'started_at' => $startedAt,
            'completed_at' => $this->faker->dateTimeBetween($startedAt, 'now'),
            'error_message' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the backup is a full backup.
     */
    public function fullBackup(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Backup::TYPE_FULL,
            'size' => $this->faker->numberBetween(1000000000, 5000000000), // 1GB to 5GB
        ]);
    }

    /**
     * Indicate that the backup is an incremental backup.
     */
    public function incrementalBackup(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Backup::TYPE_INCREMENTAL,
            'size' => $this->faker->numberBetween(100000000, 1000000000), // 100MB to 1GB
        ]);
    }
}
