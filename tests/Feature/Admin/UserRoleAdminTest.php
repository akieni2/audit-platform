<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_privileged_user_cannot_access_admin_users_page(): void
    {
        $user = User::factory()->create(['role' => 'auditeur']);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_legacy_admin_can_open_administration_users_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }
}
