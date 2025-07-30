<?php

namespace Database\Factories;

use App\Models\CustomField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Custom Field Factory
 * 
 * Creates fake custom field instances for testing
 */
class CustomFieldFactory extends Factory
{
    protected $model = CustomField::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['text', 'number', 'date', 'select', 'multiselect', 'checkbox', 'textarea', 'url', 'email'];
        $appliesTo = ['tasks', 'projects', 'users', 'clients'];
        $type = $this->faker->randomElement($types);
        
        return [
            'workspace_id' => 1, // Will be overridden in tests
            'name' => $this->faker->words(2, true),
            'key' => $this->faker->unique()->slug(2),
            'type' => $type,
            'applies_to' => $this->faker->randomElement($appliesTo),
            'options' => $this->getOptionsForType($type),
            'is_required' => $this->faker->boolean(30),
            'order' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Generate appropriate options based on field type
     */
    private function getOptionsForType(string $type): ?array
    {
        if (!in_array($type, ['select', 'multiselect'])) {
            return null;
        }

        return $this->faker->randomElements([
            'Option 1', 'Option 2', 'Option 3', 'Option 4', 'Option 5'
        ], $this->faker->numberBetween(2, 4));
    }

    /**
     * Create a text field
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'text',
            'options' => null,
        ]);
    }

    /**
     * Create a select field
     */
    public function select(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'select',
            'options' => ['Low', 'Medium', 'High'],
        ]);
    }

    /**
     * Create a required field
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }

    /**
     * Create field for specific entity type
     */
    public function forEntity(string $appliesTo): static
    {
        return $this->state(fn (array $attributes) => [
            'applies_to' => $appliesTo,
        ]);
    }
}
