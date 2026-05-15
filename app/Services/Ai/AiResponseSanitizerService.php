<?php

namespace App\Services\Ai;

class AiResponseSanitizerService
{
    /** @var list<string> */
    private array $blockedPhrases = [
        'auto-valid',
        'validation automatique',
        'j\'approuve',
        'decision finale',
        'sans validation humaine',
    ];

    public function sanitize(string $content): string
    {
        $sanitized = trim($content);

        foreach ($this->blockedPhrases as $phrase) {
            $sanitized = str_ireplace($phrase, '[modéré]', $sanitized);
        }

        if (! str_contains($sanitized, 'validation humaine')) {
            $sanitized .= "\n\n— Validation humaine requise avant toute action.";
        }

        return $sanitized;
    }

    public function detectHallucinationRisk(string $content): bool
    {
        return str_contains(strtolower($content), 'je garantis')
            || str_contains(strtolower($content), 'certitude absolue');
    }
}
