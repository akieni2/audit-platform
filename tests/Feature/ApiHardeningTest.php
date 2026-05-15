<?php

namespace Tests\Feature;

use App\Services\Api\ApiSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_signature_verification_skipped_when_disabled(): void
    {
        config(['enterprise_hardening.api_signatures' => false]);

        $request = Request::create('/api/v1/test', 'GET');
        $this->assertTrue(app(ApiSignatureService::class)->verify($request));
    }

    public function test_api_signature_can_be_generated_and_verified(): void
    {
        config(['enterprise_hardening.api_signatures' => true]);

        $service = app(ApiSignatureService::class);
        $payload = ['method' => 'GET', 'path' => 'api/v1/test', 'timestamp' => '2026-05-25', 'body' => ''];
        $signature = $service->sign($payload);

        $request = Request::create('/api/v1/test', 'GET');
        $request->headers->set('X-Api-Signature', $signature);
        $request->headers->set('X-Api-Timestamp', '2026-05-25');

        $this->assertTrue($service->verify($request));
    }
}
