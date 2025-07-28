<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up an authenticated user for testing.
     *
     * @param User|null $user
     * @return User
     */
    protected function authenticateUser(User $user): User
    {
        $user = $user ?? User::factory()->create();
        Sanctum::actingAs($user);
        return $user;
    }
}
