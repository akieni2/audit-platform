<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\Contracts\LlmDriverInterface;
use App\Services\Ai\Dto\LlmCompletionRequest;
use App\Services\Ai\Dto\LlmCompletionResponse;
use Illuminate\Support\Facades\Http;

class AzureOpenAiLlmDriver implements LlmDriverInterface
{
    public function name(): string
    {
        return 'azure_openai';
    }

    public function isConfigured(): bool
    {
        $config = config('ai_copilot.drivers.azure_openai');

        return filled($config['api_key'] ?? null)
            && filled($config['endpoint'] ?? null)
            && filled($config['deployment'] ?? null);
    }

    public function complete(LlmCompletionRequest $request): LlmCompletionResponse
    {
        if (! $this->isConfigured()) {
            return app(StubLlmDriver::class)->complete($request);
        }

        $started = microtime(true);
        $config = config('ai_copilot.drivers.azure_openai');
        $url = rtrim($config['endpoint'], '/').'/openai/deployments/'.$config['deployment'].'/chat/completions?api-version=2024-02-15-preview';

        $messages = $request->messages;
        if ($request->systemPrompt !== null) {
            array_unshift($messages, ['role' => 'system', 'content' => $request->systemPrompt]);
        }

        $response = Http::withHeaders([
            'api-key' => $config['api_key'],
        ])->timeout(60)->post($url, [
            'messages' => $messages,
            'max_tokens' => $request->maxTokens,
        ]);

        $content = $response->json('choices.0.message.content')
            ?? '【Azure OpenAI】 Suggestion assistive — validation humaine requise.';

        return new LlmCompletionResponse(
            content: (string) $content,
            driver: $this->name(),
            confidenceScore: 0.76,
            latencyMs: (int) ((microtime(true) - $started) * 1000),
            provenance: ['provider' => 'azure_openai'],
        );
    }
}
