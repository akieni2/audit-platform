<?php

namespace Tests\Feature\Iam;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TemporaryPasswordGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_one_time_temporary_password_without_email(): void
    {
        config(['mail.default' => 'array']);
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create([
            'password' => Hash::make('AncienMotDePasse!42'),
            'must_change_password' => false,
            'failed_login_attempts' => 4,
            'locked_until' => now()->addHour(),
        ]);
        $target->createToken('test-session');

        $response = $this->actingAs($admin)->post(route('admin.users.temporary-password', $target));

        $response->assertRedirect()->assertSessionHas('temporary_password');
        $temporary = session('temporary_password');
        $target->refresh();

        $this->assertIsArray($temporary);
        $this->assertTrue(Hash::check($temporary['password'], $target->password));
        $this->assertTrue($target->must_change_password);
        $this->assertNull($target->password_changed_at);
        $this->assertNull($target->locked_until);
        $this->assertSame(0, $target->failed_login_attempts);
        $this->assertSame(0, $target->tokens()->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'temporary_password_generated',
            'user_id' => $admin->id,
        ]);
        $this->assertStringNotContainsString(
            $temporary['password'],
            json_encode(AuditLog::query()->where('action', 'temporary_password_generated')->value('metadata'), JSON_THROW_ON_ERROR),
        );
    }

    public function test_admin_cannot_generate_temporary_password_for_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.users.temporary-password', $admin))
            ->assertSessionHasErrors('password');
    }

    public function test_array_mailer_replaces_email_reset_action_in_user_form(): void
    {
        config(['mail.default' => 'array']);
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $target))
            ->assertOk()
            ->assertSee('Générer un mot de passe temporaire')
            ->assertDontSee('Envoyer le lien de réinitialisation');
    }
}
