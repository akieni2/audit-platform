<?php

namespace Tests\Feature\Iam;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIntercomTest extends TestCase
{
    use RefreshDatabase;

    public function test_intercom_is_optional_for_users(): void
    {
        $user = User::factory()->create(['intercom' => null]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'intercom' => null,
        ]);
    }

    public function test_intercom_can_be_recorded_without_using_the_matricule_field(): void
    {
        $user = User::factory()->create([
            'matricule' => null,
            'intercom' => '53018',
        ]);

        $this->assertSame('53018', $user->fresh()->intercom);
        $this->assertNull($user->fresh()->matricule);
    }
}
