<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\User;
use Database\Seeders\DgcptFoundationSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_seeder_creates_protected_account(): void
    {
        $this->seed(DgcptFoundationSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $email = (string) config('dgcpt.super_admin_email', 'admin@dgcpt.ga');

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'must_change_password' => true,
        ]);

        $user = User::query()->where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isProtectedSystemAdministrator());
    }

    public function test_user_with_must_change_password_is_redirected_from_dashboard(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
            'password' => Hash::make('FactoryPass123!Valid'),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('password.force.edit'));
    }

    public function test_privileged_user_cannot_deactivate_system_super_admin(): void
    {
        $this->seed(DgcptFoundationSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $target = User::query()->where('email', config('dgcpt.super_admin_email'))->firstOrFail();
        $actor = User::factory()->create(['role' => 'admin']);

        $this->actingAs($actor)
            ->post(route('admin.users.deactivate', $target))
            ->assertSessionHasErrors();
    }

    public function test_system_super_admin_cannot_delete_own_account_via_profile(): void
    {
        $user = User::factory()->create([
            'email' => config('dgcpt.super_admin_email', 'admin@dgcpt.ga'),
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ])
            ->assertForbidden();
    }
}
