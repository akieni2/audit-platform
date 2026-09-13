<?php

namespace Tests\Feature\Auth;

use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $department = Department::query()->create([
            'name' => 'Direction test',
            'code' => 'REG',
            'type' => 'direction',
            'active' => true,
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'prenom' => 'Agent',
            'fonction' => 'Inspecteur vérificateur',
            'registration_requested_department_id' => $department->id,
            'email' => 'test@example.com',
            'password' => 'SecureExamplePass99!',
            'password_confirmation' => 'SecureExamplePass99!',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'active' => false,
            'approval_status' => 'pending',
        ]);
    }
}
