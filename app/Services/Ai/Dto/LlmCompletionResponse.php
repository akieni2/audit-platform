<?php

namespace App\Services\Ai\Dto;

readonly class LlmCompletionResponse
{
    public function __construct(
        public string $content,
        public float $confidenceScore,
        public string $driver,
        public int $latencyMs = 0,
        public ?int $tokenEstimate = null,
        public array $provenance = [],
    ) {}
}
