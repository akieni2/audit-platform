<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\Drivers\OllamaLlmDriver;
use App\Services\Ai\Dto\LlmCompletionRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OllamaLlmDriverTest extends TestCase
{
    public function test_it_calls_local_chat_api_with_resource_limits(): void
    {
        config(['ai_copilot.drivers.ollama' => [
            'base_url' => 'http://127.0.0.1:11434', 'model' => 'qwen2.5:1.5b',
            'timeout' => 120, 'connect_timeout' => 3, 'context_length' => 2048,
            'max_output_tokens' => 384, 'temperature' => 0.2, 'keep_alive' => '2m',
        ]]);
        Http::fake(['127.0.0.1:11434/*' => Http::response([
            'model' => 'qwen2.5:1.5b', 'message' => ['content' => 'Analyse locale en français.'],
            'prompt_eval_count' => 50, 'eval_count' => 80, 'done_reason' => 'stop',
        ])]);
        $result = app(OllamaLlmDriver::class)->complete(new LlmCompletionRequest(
            messages: [['role' => 'user', 'content' => 'Analyse ce risque.']],
            systemPrompt: 'Validation humaine obligatoire.', maxTokens: 1000,
        ));
        $this->assertSame('Analyse locale en français.', $result->content);
        $this->assertSame(130, $result->tokenEstimate);
        $this->assertFalse($result->provenance['remote_transfer']);
        Http::assertSent(fn ($request) => $request->url() === 'http://127.0.0.1:11434/api/chat'
            && $request['options']['num_ctx'] === 2048 && $request['options']['num_predict'] === 384
            && $request['stream'] === false);
    }

    public function test_it_returns_a_controlled_message_when_ollama_fails(): void
    {
        config(['ai_copilot.drivers.ollama' => ['base_url' => 'http://127.0.0.1:11434', 'model' => 'qwen2.5:1.5b']]);
        Http::fake(['127.0.0.1:11434/*' => Http::response(['error' => 'model unavailable'], 503)]);
        $result = app(OllamaLlmDriver::class)->complete(new LlmCompletionRequest([['role' => 'user', 'content' => 'Test']]));
        $this->assertSame(0.0, $result->confidenceScore);
        $this->assertSame('http_503', $result->provenance['reason']);
        $this->assertStringContainsString('Validation humaine', $result->content);
    }
}
