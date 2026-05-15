<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\Contracts\LlmDriverInterface;
use App\Services\Ai\Dto\LlmCompletionRequest;
use App\Services\Ai\Dto\LlmCompletionResponse;

class StubLlmDriver implements LlmDriverInterface
{
    public function name(): string
    {
        return 'stub';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function complete(LlmCompletionRequest $request): LlmCompletionResponse
    {
        $started = microtime(true);
        $userMessage = collect($request->messages)->last()['content'] ?? '';

        $content = '【Assistance IA — suggestion non contraignante】 '
            .'Analyse assistive basée sur le contexte fourni. '
            .'Validation humaine requise avant toute action. '
            .mb_substr($userMessage, 0, 280);

        return new LlmCompletionResponse(
            content: $content,
            confidenceScore: 0.72,
            driver: $this->name(),
            latencyMs: (int) ((microtime(true) - $started) * 1000),
            tokenEstimate: (int) (mb_strlen($content) / 4),
            provenance: ['mode' => 'stub', 'assistive_only' => true],
        );
    }
}
