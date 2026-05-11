<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Enrollment\AccountApprovedNotification;
use App\Notifications\Enrollment\NewEnrollmentRequestNotification;
use Database\Seeders\DgcptFoundationSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EnrollmentApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DgcptFoundationSeeder::class);
        $this->seed(SuperAdminSeeder::class);
    }

    public function test_public_registration_creates_pending_user_and_does_not_log_in(): void
    {
        Notification::fake();

        $dept = Department::query()->where('active', true)->firstOrFail();
        $super = User::query()->whereRaw('LOWER(email) = ?', [strtolower((string) config('dgcpt.super_admin_email', 'admin@dgcpt.ga'))])->firstOrFail();

        $response = $this->post(route('register'), [
            'name' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean.dupont@example.test',
            'password' => 'LongPassword123!',
            'password_confirmation' => 'LongPassword123!',
            'telephone' => '0612345678',
            'fonction' => 'Auditeur',
            'matricule' => 'MAT-001',
            'registration_requested_department_id' => $dept->id,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $this->assertGuest();

        $this->assertDatabaseHas('users', [
            'email' => 'jean.dupont@example.test',
            'active' => false,
            'approval_status' => 'pending',
            'role_id' => null,
        ]);

        Notification::assertSentTo($super, NewEnrollmentRequestNotification::class);
    }

    public function test_pending_user_cannot_login(): void
    {
        $dept = Department::query()->where('active', true)->firstOrFail();

        $user = User::factory()->pendingEnrollment()->create([
            'email' => 'pending@example.test',
            'password' => Hash::make('secret-password-123'),
            'registration_requested_department_id' => $dept->id,
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret-password-123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_super_admin_can_approve_and_user_receives_notification(): void
    {
        Notification::fake();

        $dept = Department::query()->where('active', true)->firstOrFail();
        $superRole = Role::query()->where('slug', 'super_admin')->firstOrFail();
        $assignRole = Role::query()->where('slug', 'charge_verification')->firstOrFail();

        $super = User::factory()->create([
            'email' => 'super@example.test',
            'password' => Hash::make('super-secret-123'),
            'role_id' => $superRole->id,
            'department_id' => $dept->id,
            'active' => true,
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);

        $pending = User::factory()->pendingEnrollment()->create([
            'email' => 'newbie@example.test',
            'password' => Hash::make('x'),
            'registration_requested_department_id' => $dept->id,
        ]);

        $this->actingAs($super)->post(route('admin.enrollments.approve', $pending), [
            'role_id' => $assignRole->id,
            'department_id' => $dept->id,
        ])->assertRedirect(route('admin.enrollments.index', ['status' => 'pending']));

        $pending->refresh();
        $this->assertTrue($pending->isApproved());
        $this->assertTrue($pending->active);
        $this->assertSame($assignRole->id, (int) $pending->role_id);

        Notification::assertSentTo($pending, AccountApprovedNotification::class);
    }

    public function test_non_super_admin_cannot_access_enrollment_index(): void
    {
        $dept = Department::query()->where('active', true)->firstOrFail();
        $adminRole = Role::query()->where('slug', 'admin')->firstOrFail();

        $admin = User::factory()->create([
            'email' => 'techadmin@example.test',
            'password' => Hash::make('secret'),
            'role_id' => $adminRole->id,
            'department_id' => $dept->id,
            'active' => true,
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.enrollments.index'))->assertForbidden();
    }
}
