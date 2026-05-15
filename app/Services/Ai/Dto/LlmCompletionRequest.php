<?php

namespace App\Services\Ai\Dto;

readonly class LlmCompletionRequest
{
    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function __construct(
        public array $messages,
        public ?string $systemPrompt = null,
        public int $maxTokens = 2048,
        public array $metadata = [],
    ) {}
}
