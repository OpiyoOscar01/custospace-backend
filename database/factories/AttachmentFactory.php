<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating test Attachment instances.
 */
class AttachmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Attachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mimeTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'text/plain', 'application/msword',
            'video/mp4', 'audio/mpeg'
        ];

        $name = $this->faker->word . '.' . $this->faker->fileExtension;

        return [
            'user_id' => User::factory(),
            'attachable_type' => 'App\\Models\\Post', // Example attachable
            'attachable_id' => $this->faker->numberBetween(1, 100),
            'name' => $name,
            'original_name' => $name,
            'path' => 'attachments/' . $this->faker->uuid . '/' . $name,
            'disk' => 'public',
            'mime_type' => $this->faker->randomElement($mimeTypes),
            'size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'metadata' => [
                'width' => $this->faker->optional()->numberBetween(100, 1920),
                'height' => $this->faker->optional()->numberBetween(100, 1080),
                'duration' => $this->faker->optional()->numberBetween(10, 300),
            ],
        ];
    }

    /**
     * Configure the factory for image attachments.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => $this->faker->randomElement(['image/jpeg', 'image/png', 'image/gif']),
            'metadata' => [
                'width' => $this->faker->numberBetween(100, 1920),
                'height' => $this->faker->numberBetween(100, 1080),
            ],
        ]);
    }

    /**
     * Configure the factory for document attachments.
     */
    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => $this->faker->randomElement(['application/pdf', 'text/plain', 'application/msword']),
            'metadata' => [
                'pages' => $this->faker->numberBetween(1, 50),
            ],
        ]);
    }
}
