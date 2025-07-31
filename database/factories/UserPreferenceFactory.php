<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserPreference>
 */
class UserPreferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'key' => $this->faker->randomElement([
                'theme',
                'language',
                'timezone',
                'notifications',
                'email_frequency',
                'dashboard_layout',
                'currency',
                'date_format'
            ]),
            'value' => $this->faker->randomElement([
                'dark',
                'light',
                'en',
                'es',
                'fr',
                'UTC',
                'America/New_York',
                'Europe/London',
                'true',
                'false',
                'daily',
                'weekly',
                'monthly',
                'grid',
                'list',
                'USD',
                'EUR',
                'GBP',
                'Y-m-d',
                'd/m/Y',
                'm/d/Y'
            ]),
        ];
    }

    /**
     * Create a preference with a specific key-value pair
     */
    public function withKeyValue(string $key, string $value): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Create a theme preference
     */
    public function theme(string $theme = 'dark'): static
    {
        return $this->withKeyValue('theme', $theme);
    }

    /**
     * Create a language preference
     */
    public function language(string $language = 'en'): static
    {
        return $this->withKeyValue('language', $language);
    }

    /**
     * Create a timezone preference
     */
    public function timezone(string $timezone = 'UTC'): static
    {
        return $this->withKeyValue('timezone', $timezone);
    }
}
