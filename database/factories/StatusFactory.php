<?php

namespace Database\Factories;

use App\Models\Status;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Status Factory
 */
class StatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Status::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->word();
        $types = array_keys(Status::TYPES);
        
        return [
            'workspace_id' => Workspace::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'color' => $this->faker->hexColor(),
            'icon' => $this->faker->randomElement(['check', 'flag', 'star', 'clock', 'exclamation']),
            'order' => $this->faker->numberBetween(0, 10),
            'type' => $this->faker->randomElement($types),
            'is_default' => false,
        ];
    }
    
    /**
     * Indicate that the status is a default status.
     */
    public function isDefault(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
    
    /**
     * Create a status with specific type.
     */
    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
    
    /**
     * Create a backlog status.
     */
    public function backlog(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Backlog',
            'slug' => 'backlog',
            'color' => '#6B7280',
            'type' => 'backlog',
            'order' => 0,
        ]);
    }
    
    /**
     * Create a todo status.
     */
    public function todo(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'To Do',
            'slug' => 'todo',
            'color' => '#3B82F6',
            'type' => 'todo',
            'order' => 1,
        ]);
    }
    
    /**
     * Create an in progress status.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'In Progress',
            'slug' => 'in-progress',
            'color' => '#F59E0B',
            'type' => 'in_progress',
            'order' => 2,
        ]);
    }
    
    /**
     * Create a done status.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Done',
            'slug' => 'done',
            'color' => '#10B981',
            'type' => 'done',
            'order' => 3,
        ]);
    }
}
