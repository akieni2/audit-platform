<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\Drivers\OpenAiLlmDriver;
use App\Services\Ai\Dto\LlmCompletionRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiLlmDriverTest extends TestCase
{
    public function test_it_uses_the_responses_api_without_remote_storage(): void
    {
        config([
            'ai_copilot.drivers.openai' => [
                'api_key' => 'test-key',
                'model' => 'gpt-test',
                'endpoint' => 'https://api.openai.test/v1/responses',
                'timeout' => 10,
                'connect_timeout' => 2,
                'retry_times' => 1,
                'store' => false,
            ],
        ]);

        Http::fake([
            'api.openai.test/*' => Http::response([
                'id' => 'resp_test',
                'model' => 'gpt-test',
                'output' => [[
                    'type' => 'message',
                    'content' => [[
                        'type' => 'output_text',
                        'text' => 'Analyse assistive.',
                    ]],
                ]],
                'usage' => ['total_tokens' => 42],
            ], 200, ['x-request-id' => 'req_test']),
        ]);

        $response = app(OpenAiLlmDriver::class)->complete(new LlmCompletionRequest(
            messages: [['role' => 'user', 'content' => 'Analyse cette mission.']],
            systemPrompt: 'RÃ©ponds en franÃ§ais.',
            maxTokens: 500,
        ));

        $this->assertSame('Analyse assistive.', $response->content);
        $this->assertSame(42, $response->tokenEstimate);
        $this->assertSame('resp_test', $response->provenance['response_id']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.openai.test/v1/responses'
                && $request['model'] === 'gpt-test'
                && $request['instructions'] === 'RÃ©ponds en franÃ§ais.'
                && $request['max_output_tokens'] === 500
                && $request['store'] === false
                && $request->hasHeader('Authorization', 'Bearer test-key');
        });
    }

    public function test_it_does_not_simulate_ai_when_openai_is_not_configured(): void
    {
        config([
            'ai_copilot.drivers.openai' => [
                'api_key' => null,
                'model' => 'gpt-test',
                'endpoint' => 'https://api.openai.test/v1/responses',
            ],
        ]);

        $response = app(OpenAiLlmDriver::class)->complete(new LlmCompletionRequest(
            messages: [['role' => 'user', 'content' => 'Analyse.']],
        ));

        $this->assertSame('openai', $response->driver);
        $this->assertSame(0.0, $response->confidenceScore);
        $this->assertSame('not_configured', $response->provenance['reason']);
        $this->assertStringContainsString('OpenAI', $response->content);
        Http::assertNothingSent();
    }

    public function test_it_hides_provider_error_details_from_users(): void
    {
        config([
            'ai_copilot.drivers.openai' => [
                'api_key' => 'test-key',
                'model' => 'gpt-test',
                'endpoint' => 'https://api.openai.test/v1/responses',
                'timeout' => 10,
                'connect_timeout' => 2,
                'retry_times' => 1,
                'store' => false,
            ],
        ]);

        Http::fake([
            'api.openai.test/*' => Http::response(['error' => ['message' => 'Unauthorized']], 401),
        ]);

        $response = app(OpenAiLlmDriver::class)->complete(new LlmCompletionRequest(
            messages: [['role' => 'user', 'content' => 'Analyse.']],
        ));

        $this->assertSame(0.0, $response->confidenceScore);
        $this->assertSame('http_401', $response->provenance['reason']);
        $this->assertStringNotContainsString('Unauthorized', $response->content);
    }
}
