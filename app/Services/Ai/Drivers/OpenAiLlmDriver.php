<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\Contracts\LlmDriverInterface;
use App\Services\Ai\Dto\LlmCompletionRequest;
use App\Services\Ai\Dto\LlmCompletionResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OpenAiLlmDriver implements LlmDriverInterface
{
    public function name(): string
    {
        return 'openai';
    }

    public function isConfigured(): bool
    {
        $config = config('ai_copilot.drivers.openai');

        return filled($config['api_key'] ?? null)
            && filled($config['model'] ?? null)
            && filled($config['endpoint'] ?? null);
    }

    public function complete(LlmCompletionRequest $request): LlmCompletionResponse
    {
        if (! $this->isConfigured()) {
            return $this->unavailableResponse(
                'Le service OpenAI n’est pas configuré. Contactez l’administrateur de la plateforme.',
                'not_configured',
            );
        }

        $started = microtime(true);
        $config = config('ai_copilot.drivers.openai');

        try {
            $response = Http::withToken($config['api_key'])
                ->acceptJson()
                ->asJson()
                ->connectTimeout((int) $config['connect_timeout'])
                ->timeout((int) $config['timeout'])
                ->retry(max(1, (int) $config['retry_times']), 300, throw: false)
                ->post($config['endpoint'], [
                    'model' => $config['model'],
                    'instructions' => $request->systemPrompt,
                    'input' => $request->messages,
                    'max_output_tokens' => $request->maxTokens,
                    'store' => (bool) $config['store'],
                ]);
        } catch (ConnectionException) {
            return $this->unavailableResponse(
                'Impossible de joindre OpenAI. Vérifiez la connexion réseau du serveur.',
                'connection_error',
                (int) ((microtime(true) - $started) * 1000),
            );
        }

        $latency = (int) ((microtime(true) - $started) * 1000);
        $requestId = $response->header('x-request-id');

        if (! $response->successful()) {
            return $this->unavailableResponse(
                'Le service OpenAI est temporairement indisponible. Réessayez ultérieurement.',
                'http_'.$response->status(),
                $latency,
                $requestId,
            );
        }

        $content = $this->extractOutputText((array) $response->json());

        if ($content === null) {
            return $this->unavailableResponse(
                'OpenAI n’a retourné aucun contenu exploitable. Une validation humaine reste requise.',
                'empty_response',
                $latency,
                $requestId,
            );
        }

        return new LlmCompletionResponse(
            content: $content,
            confidenceScore: 0.75,
            driver: $this->name(),
            latencyMs: $latency,
            tokenEstimate: $response->json('usage.total_tokens'),
            provenance: array_filter([
                'provider' => 'openai',
                'model' => $response->json('model') ?? $config['model'],
                'response_id' => $response->json('id'),
                'request_id' => $requestId,
                'stored_remotely' => (bool) $config['store'],
            ], static fn (mixed $value): bool => $value !== null),
        );
    }

    private function extractOutputText(array $payload): ?string
    {
        $topLevel = data_get($payload, 'output_text');
        if (is_string($topLevel) && filled(trim($topLevel))) {
            return trim($topLevel);
        }

        $parts = [];
        foreach ((array) data_get($payload, 'output', []) as $item) {
            foreach ((array) data_get($item, 'content', []) as $content) {
                if (data_get($content, 'type') !== 'output_text') {
                    continue;
                }

                $text = data_get($content, 'text');
                if (is_string($text) && filled(trim($text))) {
                    $parts[] = trim($text);
                }
            }
        }

        $text = trim(implode("\n", $parts));

        return $text !== '' ? $text : null;
    }

    private function unavailableResponse(
        string $message,
        string $reason,
        int $latencyMs = 0,
        ?string $requestId = null,
    ): LlmCompletionResponse {
        return new LlmCompletionResponse(
            content: $message."\n\n— Validation humaine requise avant toute action.",
            confidenceScore: 0.0,
            driver: $this->name(),
            latencyMs: $latencyMs,
            provenance: array_filter([
                'provider' => 'openai',
                'status' => 'unavailable',
                'reason' => $reason,
                'request_id' => $requestId,
                'stored_remotely' => false,
            ], static fn (mixed $value): bool => $value !== null),
        );
    }
}
