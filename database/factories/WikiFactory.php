<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wiki;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Wiki Factory - Creates fake wiki instances for testing
 */
class WikiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Wiki::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(rand(3, 8));
        
        return [
            'workspace_id' => Workspace::factory(),
            'created_by_id' => User::factory(),
            'parent_id' => null,
            'title' => $title,
            'slug' => \Str::slug($title),
            'content' => fake()->paragraphs(rand(3, 10), true),
            'is_published' => fake()->boolean(70), // 70% chance of being published
            'metadata' => [
                'tags' => fake()->words(rand(1, 5)),
                'description' => fake()->sentence(),
                'estimated_read_time' => rand(2, 15),
            ],
        ];
    }

    /**
     * Indicate that the wiki is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }

    /**
     * Indicate that the wiki is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }

    /**
     * Indicate that the wiki has a parent.
     */
    public function withParent(Wiki $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent?->id ?? Wiki::factory()->create()->id,
            'workspace_id' => $parent?->workspace_id ?? $attributes['workspace_id'],
        ]);
    }

    /**
     * Create wiki with specific workspace.
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Create wiki by specific user.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by_id' => $user->id,
        ]);
    }

    /**
     * Create wiki with collaborators.
     */
    public function withCollaborators(array $collaborators = []): static
    {
        return $this->state(function (array $attributes) use ($collaborators) {
            $metadata = $attributes['metadata'] ?? [];
            $metadata['collaborators'] = empty($collaborators) 
                ? [
                    [
                        'user_id' => User::factory()->create()->id,
                        'role' => fake()->randomElement(['viewer', 'collaborator', 'editor']),
                        'assigned_at' => now()->toISOString(),
                    ]
                ]
                : $collaborators;

            return ['metadata' => $metadata];
        });
    }

    /**
     * Create wiki tree structure.
     */
    public function tree(int $depth = 2, int $childrenPerLevel = 3): static
    {
        return $this->afterCreating(function (Wiki $wiki) use ($depth, $childrenPerLevel) {
            if ($depth > 0) {
                Wiki::factory()
                    ->count($childrenPerLevel)
                    ->withParent($wiki)
                    ->tree($depth - 1, $childrenPerLevel)
                    ->create();
            }
        });
    }
}
