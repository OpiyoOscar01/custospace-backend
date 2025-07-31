<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Form Factory
 * 
 * Generates fake form data for testing
 */
class FormFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Form::class;

    /**
     * Define the model's default state.
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(3, true);
        $slug = Str::slug($name);

        return [
            'workspace_id' => Workspace::factory(),
            'created_by_id' => User::factory(),
            'name' => $name,
            'slug' => $slug,
            'description' => $this->faker->optional()->paragraph(),
            'fields' => $this->generateFormFields(),
            'settings' => $this->generateFormSettings(),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the form is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the form is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a form with simple fields.
     */
    public function simple(): static
    {
        return $this->state(fn (array $attributes) => [
            'fields' => [
                [
                    'name' => 'name',
                    'type' => 'text',
                    'label' => 'Full Name',
                    'required' => true,
                    'placeholder' => 'Enter your full name',
                ],
                [
                    'name' => 'email',
                    'type' => 'email',
                    'label' => 'Email Address',
                    'required' => true,
                    'placeholder' => 'your@email.com',
                ],
                [
                    'name' => 'message',
                    'type' => 'textarea',
                    'label' => 'Message',
                    'required' => false,
                    'placeholder' => 'Your message here...',
                ],
            ],
        ]);
    }

    /**
     * Create a form with complex fields.
     */
    public function complex(): static
    {
        return $this->state(fn (array $attributes) => [
            'fields' => $this->generateComplexFormFields(),
        ]);
    }

    /**
     * Generate random form fields.
     */
    private function generateFormFields(): array
    {
        $fieldTypes = ['text', 'email', 'number', 'textarea', 'select', 'checkbox', 'radio'];
        $fieldCount = $this->faker->numberBetween(3, 8);
        $fields = [];

        for ($i = 0; $i < $fieldCount; $i++) {
            $type = $this->faker->randomElement($fieldTypes);
            $name = $this->faker->unique()->word();
            
            $field = [
                'name' => Str::slug($name, '_'),
                'type' => $type,
                'label' => Str::title($name),
                'required' => $this->faker->boolean(60),
                'placeholder' => $this->faker->optional()->sentence(3),
                'help_text' => $this->faker->optional()->sentence(),
            ];

            // Add options for select, radio, and checkbox fields
            if (in_array($type, ['select', 'radio', 'checkbox'])) {
                $field['options'] = $this->faker->words($this->faker->numberBetween(2, 5));
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Generate complex form fields.
     */
    private function generateComplexFormFields(): array
    {
        return [
            [
                'name' => 'personal_info_title',
                'type' => 'header',
                'label' => 'Personal Information',
                'required' => false,
            ],
            [
                'name' => 'first_name',
                'type' => 'text',
                'label' => 'First Name',
                'required' => true,
                'placeholder' => 'John',
            ],
            [
                'name' => 'last_name',
                'type' => 'text',
                'label' => 'Last Name',
                'required' => true,
                'placeholder' => 'Doe',
            ],
            [
                'name' => 'email',
                'type' => 'email',
                'label' => 'Email Address',
                'required' => true,
                'placeholder' => 'john@example.com',
            ],
            [
                'name' => 'phone',
                'type' => 'text',
                'label' => 'Phone Number',
                'required' => false,
                'placeholder' => '+1 (555) 123-4567',
            ],
            [
                'name' => 'age',
                'type' => 'number',
                'label' => 'Age',
                'required' => true,
                'min' => 18,
                'max' => 100,
            ],
            [
                'name' => 'gender',
                'type' => 'select',
                'label' => 'Gender',
                'required' => false,
                'options' => ['Male', 'Female', 'Other', 'Prefer not to say'],
            ],
            [
                'name' => 'interests',
                'type' => 'checkbox',
                'label' => 'Interests',
                'required' => false,
                'options' => ['Technology', 'Sports', 'Music', 'Travel', 'Reading', 'Cooking'],
            ],
            [
                'name' => 'experience_level',
                'type' => 'radio',
                'label' => 'Experience Level',
                'required' => true,
                'options' => ['Beginner', 'Intermediate', 'Advanced', 'Expert'],
            ],
            [
                'name' => 'comments',
                'type' => 'textarea',
                'label' => 'Additional Comments',
                'required' => false,
                'placeholder' => 'Any additional information you\'d like to share...',
            ],
        ];
    }

    /**
     * Generate form settings.
     */
    private function generateFormSettings(): array
    {
        return [
            'allow_multiple_submissions' => $this->faker->boolean(30),
            'require_authentication' => $this->faker->boolean(40),
            'notification_email' => $this->faker->optional()->email(),
            'success_message' => $this->faker->optional()->sentence(),
            'submit_button_text' => $this->faker->randomElement(['Submit', 'Send', 'Save', 'Continue']),
            'edit_time_limit' => $this->faker->randomElement([30, 60, 120, 240]), // minutes
        ];
    }
}
