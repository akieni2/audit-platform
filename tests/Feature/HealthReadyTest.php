<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthReadyTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_ready_returns_json_and_database_flag(): void
    {
        $response = $this->getJson(route('health.ready'));

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'database',
            'timestamp',
        ]);
        $this->assertTrue($response->json('database'));
    }
}
