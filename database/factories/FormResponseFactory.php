<?php

namespace Database\Factories;

use App\Models\FormResponse;
use App\Models\Form;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Form Response Factory
 * 
 * Generates fake form response data for testing
 */
class FormResponseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = FormResponse::class;

    /**
     * Define the model's default state.
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'user_id' => $this->faker->optional(0.7)->randomElement([
                User::factory(),
                null // 30% chance of anonymous submission
            ]),
            'data' => [], // Will be generated based on form fields
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    /**
     * Create response for a specific form.
     */
    public function forForm(Form $form): static
    {
        return $this->state(fn (array $attributes) => [
            'form_id' => $form->id,
            'data' => $this->generateResponseDataForForm($form),
        ]);
    }

    /**
     * Create anonymous response.
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Create authenticated response.
     */
    public function authenticated(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (FormResponse $formResponse) {
            // Generate response data based on form fields if not already set
            if (empty($formResponse->data) && $formResponse->form) {
                $formResponse->data = $this->generateResponseDataForForm($formResponse->form);
            }
        });
    }

    /**
     * Generate response data based on form fields.
     */
    private function generateResponseDataForForm(Form $form): array
    {
        $responseData = [];

        foreach ($form->fields as $field) {
            $fieldName = $field['name'];
            $fieldType = $field['type'];
            $isRequired = $field['required'] ?? false;

            // Skip optional fields sometimes
            if (!$isRequired && $this->faker->boolean(30)) {
                continue;
            }

            $responseData[$fieldName] = $this->generateFieldValue($field);
        }

        return $responseData;
    }

    /**
     * Generate a value for a specific field.
     */
    private function generateFieldValue(array $field)
    {
        $fieldType = $field['type'];
        $fieldName = $field['name'];

        switch ($fieldType) {
            case 'text':
                return $this->generateTextValue($fieldName);
            
            case 'email':
                return $this->faker->email();
            
            case 'number':
                $min = $field['min'] ?? 1;
                $max = $field['max'] ?? 100;
                return $this->faker->numberBetween($min, $max);
            
            case 'textarea':
                return $this->faker->paragraph();
            
            case 'select':
            case 'radio':
                $options = $field['options'] ?? ['Option 1', 'Option 2', 'Option 3'];
                return $this->faker->randomElement($options);
            
            case 'checkbox':
                $options = $field['options'] ?? ['Option 1', 'Option 2', 'Option 3'];
                $selectedCount = $this->faker->numberBetween(0, count($options));
                return $this->faker->randomElements($options, $selectedCount);
            
            case 'date':
                return $this->faker->date();
            
            case 'datetime':
                return $this->faker->dateTime()->format('Y-m-d H:i:s');
            
            case 'file':
                return 'uploaded_file_' . $this->faker->uuid() . '.pdf';
            
            default:
                return $this->faker->word();
        }
    }

    /**
     * Generate contextual text value based on field name.
     */
    private function generateTextValue(string $fieldName): string
    {
        return match (strtolower($fieldName)) {
            'name', 'full_name', 'fullname' => $this->faker->name(),
            'first_name', 'firstname' => $this->faker->firstName(),
            'last_name', 'lastname' => $this->faker->lastName(),
            'company', 'company_name' => $this->faker->company(),
            'phone', 'phone_number' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),
            'website', 'url' => $this->faker->url(),
            'title', 'job_title' => $this->faker->jobTitle(),
            default => $this->faker->words(3, true),
        };
    }
}
