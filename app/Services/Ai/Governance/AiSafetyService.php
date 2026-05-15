<?php

namespace App\Services\Ai\Governance;

use App\Services\Ai\AiResponseSanitizerService;

class AiSafetyService
{
    public function __construct(
        private AiResponseSanitizerService $sanitizer,
        private AiModerationService $moderation,
    ) {}

    public function validateResponse(string $content): array
    {
        $sanitized = $this->sanitizer->sanitize($content);
        $moderation = $this->moderation->moderate($sanitized);

        return [
            'content' => $sanitized,
            'safe' => $moderation['allowed'],
            'hallucination_risk' => $this->sanitizer->detectHallucinationRisk($sanitized),
            'flags' => $moderation['flags'],
        ];
    }
}
