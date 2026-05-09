<?php

namespace Tests\Feature\Iam;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_locks_after_repeated_failed_password_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'lockme@example.gov',
            'password' => Hash::make('Correct-Horse-99'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $user->refresh();
        $this->assertNotNull($user->locked_until);
    }
}
