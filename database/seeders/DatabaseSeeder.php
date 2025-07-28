<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        // Create regular users
        $users = User::factory(5)->create();
        $allUsers = $users->prepend($admin);

        // Create workspaces
        $workspaces = Workspace::factory(3)->create();

        // Attach admin to all workspaces as owner
        foreach ($workspaces as $workspace) {
            $workspace->users()->attach($admin->id, [
                'role' => 'owner',
                'joined_at' => now(),
            ]);
            
            // Create teams for each workspace
            $teams = Team::factory(2)->create(['workspace_id' => $workspace->id]);
            
            // Attach users to workspaces and teams
            foreach ($users as $index => $user) {
                // Attach user to workspace with different roles
                $role = $index === 0 ? 'admin' : ($index === 1 ? 'member' : 'viewer');
                $workspace->users()->attach($user->id, [
                    'role' => $role,
                    'joined_at' => now(),
                ]);
                
                // Attach some users to teams
                if ($index < 3) {
                    foreach ($teams as $teamIndex => $team) {
                        $teamRole = $index === 0 ? 'owner' : 'member';
                        $team->users()->attach($user->id, [
                            'role' => $teamRole,
                            'joined_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
}
