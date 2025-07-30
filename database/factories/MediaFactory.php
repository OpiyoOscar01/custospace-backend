<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating test Media instances.
 */
class MediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Media::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mimeTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/webm', 'audio/mpeg', 'audio/wav'
        ];

        $collections = ['avatars', 'banners', 'documents', 'videos', 'audios', null];
        $name = $this->faker->word . '.' . $this->faker->fileExtension;

        return [
            'workspace_id' => Workspace::factory(),
            'user_id' => User::factory(),
            'name' => $name,
            'original_name' => $name,
            'path' => 'media/' . $this->faker->uuid . '/' . $name,
            'disk' => 'public',
            'mime_type' => $this->faker->randomElement($mimeTypes),
            'size' => $this->faker->numberBetween(1024, 52428800), // 1KB to 50MB
            'collection' => $this->faker->randomElement($collections),
            'metadata' => [
                'width' => $this->faker->optional()->numberBetween(100, 1920),
                'height' => $this->faker->optional()->numberBetween(100, 1080),
                'duration' => $this->faker->optional()->numberBetween(10, 300),
                'bitrate' => $this->faker->optional()->numberBetween(128, 1024),
            ],
        ];
    }

    /**
     * Configure the factory for image media.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => $this->faker->randomElement(['image/jpeg', 'image/png', 'image/gif']),
            'collection' => $this->faker->randomElement(['avatars', 'banners', 'gallery']),
            'metadata' => [
                'width' => $this->faker->numberBetween(100, 1920),
                'height' => $this->faker->numberBetween(100, 1080),
            ],
        ]);
    }

    /**
     * Configure the factory for video media.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => $this->faker->randomElement(['video/mp4', 'video/webm']),
            'collection' => 'videos',
            'metadata' => [
                'width' => $this->faker->numberBetween(720, 1920),
                'height' => $this->faker->numberBetween(480, 1080),
                'duration' => $this->faker->numberBetween(30, 3600),
                'bitrate' => $this->faker->numberBetween(500, 2000),
            ],
        ]);
    }
}
