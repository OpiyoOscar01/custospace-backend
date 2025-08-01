<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Class InvitationFactory
 * 
 * Factory for creating invitation test instances
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Invitation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'team_id' => $this->faker->boolean(30) ? Team::factory() : null,
            'invited_by_id' => User::factory(),
            'email' => $this->faker->unique()->safeEmail(),
            'token' => Str::random(64),
            'role' => $this->faker->randomElement(['owner', 'admin', 'member', 'viewer']),
            'status' => $this->faker->randomElement(['pending', 'accepted', 'declined', 'expired']),
            'metadata' => $this->faker->boolean(50) ? [
                'source' => $this->faker->randomElement(['web', 'api', 'import']),
                'custom_message' => $this->faker->sentence(),
            ] : null,
            'expires_at' => $this->faker->dateTimeBetween('now', '+30 days'),
        ];
    }

    /**
     * State for pending invitations
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
        ]);
    }

    /**
     * State for accepted invitations
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
        ]);
    }

    /**
     * State for expired invitations
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * State for admin role invitations
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * State for member role invitations
     */
    public function member(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'member',
        ]);
    }

    /**
     * State for invitations with team assignment
     */
    public function withTeam(): static
    {
        return $this->state(fn (array $attributes) => [
            'team_id' => Team::factory(),
        ]);
    }

    /**
     * State for invitations without team assignment
     */
    public function withoutTeam(): static
    {
        return $this->state(fn (array $attributes) => [
            'team_id' => null,
        ]);
    }
}