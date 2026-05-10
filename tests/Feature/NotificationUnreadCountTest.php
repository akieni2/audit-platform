<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationUnreadCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_unread_count_requires_authentication(): void
    {
        $this->getJson(route('notifications.unread-count'))->assertUnauthorized();
    }

    public function test_unread_count_returns_json_number(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('notifications.unread-count'));

        $response->assertOk();
        $response->assertJsonStructure(['count']);
        $response->assertJson(['count' => 0]);
    }
}
