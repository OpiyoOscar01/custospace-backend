<?php
// database/factories/AuditLogFactory.php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Audit Log Factory
 * 
 * Generates fake audit log data for testing
 */
class AuditLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $events = ['created', 'updated', 'deleted', 'restored'];
        $auditableTypes = [
            'App\\Models\\Project',
            'App\\Models\\Task',
            'App\\Models\\Document',
            'App\\Models\\User',
        ];

        $event = $this->faker->randomElement($events);

        return [
            'user_id' => User::factory(),
            'event' => $event,
            'auditable_type' => $this->faker->randomElement($auditableTypes),
            'auditable_id' => $this->faker->numberBetween(1, 100),
            'old_values' => $this->generateOldValues($event),
            'new_values' => $this->generateNewValues($event),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'created_at' => $this->faker->dateTimeBetween('-90 days', 'now'),
        ];
    }

    /**
     * Generate old values based on event.
     */
    private function generateOldValues(string $event): ?array
    {
        if ($event === 'created') {
            return null;
        }

        return [
            'name' => $this->faker->words(3, true),
            'status' => $this->faker->randomElement(['draft', 'pending', 'active']),
            'description' => $this->faker->sentence(),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'updated_at' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Generate new values based on event.
     */
    private function generateNewValues(string $event): ?array
    {
        if ($event === 'deleted') {
            return null;
        }

        return [
            'name' => $this->faker->words(3, true),
            'status' => $this->faker->randomElement(['pending', 'active', 'completed']),
            'description' => $this->faker->sentence(),
            'priority' => $this->faker->randomElement(['medium', 'high', 'urgent']),
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Create audit log for specific event.
     */
    public function forEvent(string $event): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => $event,
            'old_values' => $this->generateOldValues($event),
            'new_values' => $this->generateNewValues($event),
        ]);
    }

    /**
     * Create audit log for specific user.
     */
    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Create audit log for specific auditable model.
     */
    public function forAuditable(string $type, int $id): static
    {
        return $this->state(fn (array $attributes) => [
            'auditable_type' => $type,
            'auditable_id' => $id,
        ]);
    }
}
