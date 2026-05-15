<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\Contracts\LlmDriverInterface;
use App\Services\Ai\Dto\LlmCompletionRequest;
use App\Services\Ai\Dto\LlmCompletionResponse;
use Illuminate\Support\Facades\Http;

class OpenAiLlmDriver implements LlmDriverInterface
{
    public function name(): string
    {
        return 'openai';
    }

    public function isConfigured(): bool
    {
        return filled(config('ai_copilot.drivers.openai.api_key'));
    }

    public function complete(LlmCompletionRequest $request): LlmCompletionResponse
    {
        if (! $this->isConfigured()) {
            return app(StubLlmDriver::class)->complete($request);
        }

        $started = microtime(true);
        $config = config('ai_copilot.drivers.openai');

        $messages = $request->messages;
        if ($request->systemPrompt !== null) {
            array_unshift($messages, ['role' => 'system', 'content' => $request->systemPrompt]);
        }

        $response = Http::withToken($config['api_key'])
            ->timeout(60)
            ->post($config['endpoint'], [
                'model' => $config['model'],
                'messages' => $messages,
                'max_tokens' => $request->maxTokens,
            ]);

        $content = $response->json('choices.0.message.content')
            ?? '【IA】 Réponse indisponible — validation humaine requise.';

        return new LlmCompletionResponse(
            content: (string) $content,
            confidenceScore: 0.75,
            driver: $this->name(),
            latencyMs: (int) ((microtime(true) - $started) * 1000),
            provenance: ['provider' => 'openai'],
        );
    }
}
