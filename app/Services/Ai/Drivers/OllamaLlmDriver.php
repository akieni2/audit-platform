<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\Contracts\LlmDriverInterface;
use App\Services\Ai\Dto\LlmCompletionRequest;
use App\Services\Ai\Dto\LlmCompletionResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OllamaLlmDriver implements LlmDriverInterface
{
    public function name(): string { return 'ollama'; }

    public function isConfigured(): bool
    {
        $config = config('ai_copilot.drivers.ollama');
        return filled($config['base_url'] ?? null) && filled($config['model'] ?? null);
    }

    public function complete(LlmCompletionRequest $request): LlmCompletionResponse
    {
        $started = microtime(true);
        if (! $this->isConfigured()) {
            return $this->unavailable('L’IA locale Ollama n’est pas configurée.', 'not_configured', $started);
        }
        $config = config('ai_copilot.drivers.ollama');
        $messages = collect($request->messages)->map(fn (array $message): array => [
            'role' => in_array($message['role'] ?? '', ['system', 'assistant', 'user'], true) ? $message['role'] : 'user',
            'content' => (string) ($message['content'] ?? ''),
        ])->values()->all();
        if (filled($request->systemPrompt)) {
            array_unshift($messages, ['role' => 'system', 'content' => $request->systemPrompt]);
        }
        try {
            $response = Http::acceptJson()->asJson()
                ->connectTimeout((int) ($config['connect_timeout'] ?? 3))
                ->timeout((int) ($config['timeout'] ?? 120))
                ->post(rtrim($config['base_url'], '/').'/api/chat', [
                    'model' => $config['model'], 'messages' => $messages, 'stream' => false,
                    'keep_alive' => $config['keep_alive'] ?? '2m',
                    'options' => [
                        'num_ctx' => max(512, (int) ($config['context_length'] ?? 2048)),
                        'num_predict' => min($request->maxTokens, max(64, (int) ($config['max_output_tokens'] ?? 384))),
                        'temperature' => (float) ($config['temperature'] ?? 0.2),
                    ],
                ]);
        } catch (ConnectionException) {
            return $this->unavailable('Impossible de joindre l’IA locale Ollama.', 'connection_error', $started);
        }
        if (! $response->successful()) {
            return $this->unavailable('L’IA locale est temporairement indisponible.', 'http_'.$response->status(), $started);
        }
        $content = trim((string) $response->json('message.content'));
        if ($content === '') {
            return $this->unavailable('L’IA locale n’a retourné aucun contenu exploitable.', 'empty_response', $started);
        }
        return new LlmCompletionResponse(
            content: $content, confidenceScore: 0.7, driver: $this->name(),
            latencyMs: (int) ((microtime(true) - $started) * 1000),
            tokenEstimate: $response->json('prompt_eval_count') !== null || $response->json('eval_count') !== null
                ? (int) $response->json('prompt_eval_count', 0) + (int) $response->json('eval_count', 0) : null,
            provenance: ['provider' => 'ollama', 'model' => $response->json('model') ?? $config['model'],
                'remote_transfer' => false, 'done_reason' => $response->json('done_reason')],
        );
    }

    private function unavailable(string $message, string $reason, float $started): LlmCompletionResponse
    {
        return new LlmCompletionResponse(
            content: $message."\n\n— Validation humaine requise avant toute action.", confidenceScore: 0.0,
            driver: $this->name(), latencyMs: (int) ((microtime(true) - $started) * 1000),
            provenance: ['provider' => 'ollama', 'status' => 'unavailable', 'reason' => $reason, 'remote_transfer' => false],
        );
    }
}
