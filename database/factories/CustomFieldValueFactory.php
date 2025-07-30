<?php

namespace Database\Factories;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Custom Field Value Factory
 * 
 * Creates fake custom field value instances for testing
 */
class CustomFieldValueFactory extends Factory
{
    protected $model = CustomFieldValue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'custom_field_id' => CustomField::factory(),
            'entity_type' => 'App\\Models\\Task',
            'entity_id' => $this->faker->numberBetween(1, 100),
            'value' => $this->faker->sentence(),
        ];
    }

    /**
     * Create value for a specific custom field
     */
    public function forCustomField(CustomField $customField): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_field_id' => $customField->id,
            'value' => $this->generateValueForFieldType($customField->type, $customField->options),
        ]);
    }

    /**
     * Create value for specific entity
     */
    public function forEntity(string $entityType, int $entityId): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
    }

    /**
     * Generate appropriate value based on field type
     */
    private function generateValueForFieldType(string $type, ?array $options = null): mixed
    {
        return match ($type) {
            'text', 'textarea' => $this->faker->sentence(),
            'number' => $this->faker->randomFloat(2, 0, 1000),
            'date' => $this->faker->date(),
            'email' => $this->faker->email(),
            'url' => $this->faker->url(),
            'checkbox' => $this->faker->boolean(),
            'select' => $options ? $this->faker->randomElement($options) : 'Option 1',
            'multiselect' => $options ? json_encode($this->faker->randomElements($options, 2)) : json_encode(['Option 1', 'Option 2']),
            default => $this->faker->word(),
        };
    }
}
