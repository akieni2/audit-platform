<?php

namespace App\Domain\Ai\Enums;

enum AiConfidenceLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public static function fromScore(float $score): self
    {
        $threshold = (float) config('ai_copilot.confidence_threshold', 0.65);

        if ($score >= $threshold + 0.2) {
            return self::High;
        }

        if ($score >= $threshold) {
            return self::Medium;
        }

        return self::Low;
    }

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Faible',
            self::Medium => 'Moyenne',
            self::High => 'Élevée',
        };
    }
}
