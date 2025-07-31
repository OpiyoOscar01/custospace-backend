<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wiki;
use App\Models\WikiRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Wiki Revision Factory - Creates fake wiki revision instances for testing
 */
class WikiRevisionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = WikiRevision::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wiki_id' => Wiki::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(rand(3, 8)),
            'content' => fake()->paragraphs(rand(3, 10), true),
            'summary' => fake()->optional(0.7)->sentence(),
        ];
    }

    /**
     * Create revision for specific wiki.
     */
    public function forWiki(Wiki $wiki): static
    {
        return $this->state(fn (array $attributes) => [
            'wiki_id' => $wiki->id,
        ]);
    }

    /**
     * Create revision by specific user.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create revision with summary.
     */
    public function withSummary(string $summary = null): static
    {
        return $this->state(fn (array $attributes) => [
            'summary' => $summary ?? fake()->sentence(),
        ]);
    }

    /**
     * Create initial revision.
     */
    public function initial(): static
    {
        return $this->state(fn (array $attributes) => [
            'summary' => 'Initial version',
        ]);
    }

    /**
     * Create major revision.
     */
    public function major(): static
    {
        return $this->state(fn (array $attributes) => [
            'summary' => fake()->randomElement([
                'Major content restructure',
                'Added new sections',
                'Complete rewrite',
                'Significant updates',
            ]),
        ]);
    }

    /**
     * Create minor revision.
     */
    public function minor(): static
    {
        return $this->state(fn (array $attributes) => [
            'summary' => fake()->randomElement([
                'Fixed typos',
                'Minor corrections',
                'Updated formatting',
                'Small improvements',
            ]),
        ]);
    }
}
