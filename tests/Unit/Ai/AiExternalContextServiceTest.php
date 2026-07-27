<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\AiExternalContextService;
use Tests\TestCase;

class AiExternalContextServiceTest extends TestCase
{
    public function test_it_removes_tenant_and_secret_fields_from_external_context(): void
    {
        $serialized = app(AiExternalContextService::class)->serialize([
            'mission_id' => 14,
            'mission_organisation' => 'DSI',
            'tenant_key' => 'department:14',
            'tenant_scope' => 'department',
            'password' => 'not-for-openai',
            'nested' => [
                'api_token' => 'not-for-openai',
                'risk' => 'Sauvegarde non testee',
            ],
        ]);

        $context = json_decode($serialized, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(14, $context['mission_id']);
        $this->assertSame('DSI', $context['mission_organisation']);
        $this->assertSame('Sauvegarde non testee', $context['nested']['risk']);
        $this->assertArrayNotHasKey('tenant_key', $context);
        $this->assertArrayNotHasKey('tenant_scope', $context);
        $this->assertArrayNotHasKey('password', $context);
        $this->assertArrayNotHasKey('api_token', $context['nested']);
    }

    public function test_it_limits_the_amount_of_context_sent_to_openai(): void
    {
        config(['ai_copilot.context_max_chars' => 1000]);

        $serialized = app(AiExternalContextService::class)->serialize([
            'large_field' => str_repeat('a', 2000),
        ]);

        $this->assertLessThan(1100, mb_strlen($serialized));
        $this->assertStringContainsString('Contexte tronqu', $serialized);
    }
}
