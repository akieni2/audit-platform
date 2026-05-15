<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\Contracts\LlmDriverInterface;
use App\Services\Ai\Dto\LlmCompletionRequest;
use App\Services\Ai\Dto\LlmCompletionResponse;
use Illuminate\Support\Facades\Http;

class OllamaLlmDriver implements LlmDriverInterface
{
    public function name(): string
    {
        return 'ollama';
    }

    public function isConfigured(): bool
    {
        return filled(config('ai_copilot.drivers.ollama.base_url'));
    }

    public function complete(LlmCompletionRequest $request): LlmCompletionResponse
    {
        if (! $this->isConfigured()) {
            return app(StubLlmDriver::class)->complete($request);
        }

        $started = microtime(true);
        $config = config('ai_copilot.drivers.ollama');
        $prompt = collect($request->messages)->pluck('content')->implode("\n\n");

        $response = Http::timeout(120)
            ->post(rtrim($config['base_url'], '/').'/api/generate', [
                'model' => $config['model'],
                'prompt' => ($request->systemPrompt ? $request->systemPrompt."\n\n" : '').$prompt,
                'stream' => false,
            ]);

        $content = $response->json('response')
            ?? '【IA locale】 Suggestion assistive — validation humaine requise.';

        return new LlmCompletionResponse(
            content: (string) $content,
            confidenceScore: 0.7,
            driver: $this->name(),
            latencyMs: (int) ((microtime(true) - $started) * 1000),
            provenance: ['provider' => 'ollama'],
        );
    }
}
