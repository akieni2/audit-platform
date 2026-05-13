<?php

namespace App\Services\Risk;

use App\Domain\Risk\Enums\CriticalityLevel;

final class RiskScoringService
{
    public function __construct(
        private CriticalityEvaluationService $criticality,
    ) {}

    public function clampScale(?int $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return max(1, min(5, $value));
    }

    public function normalizeScaleValue(int|string|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_numeric($value)) {
            return $this->clampScale((int) $value);
        }

        $normalized = strtolower(trim($value));
        $normalized = str_replace(
            ['é', 'è', 'ê', 'ë', 'à', 'â', 'ä', 'î', 'ï', 'ô', 'ö', 'ù', 'û', 'ü'],
            ['e', 'e', 'e', 'e', 'a', 'a', 'a', 'i', 'i', 'o', 'o', 'u', 'u', 'u'],
            $normalized
        );

        return match ($normalized) {
            'faible', 'low', 'bas', 'basse', 'mineur', 'minor' => 1,
            'modere', 'moderee', 'moyen', 'moyenne', 'medium', 'moderate' => 3,
            'eleve', 'elevee', 'haut', 'haute', 'high', 'important', 'major' => 4,
            'critique', 'critical', 'tres_eleve', 'tres_elevee', 'severe' => 5,
            default => null,
        };
    }

    public function score(?int $impact, ?int $probability): ?int
    {
        $impact = $this->clampScale($impact);
        $probability = $this->clampScale($probability);

        if ($impact === null || $probability === null) {
            return null;
        }

        return $impact * $probability;
    }

    public function criticality(?int $impact, ?int $probability): ?CriticalityLevel
    {
        $score = $this->score($impact, $probability);

        return $score === null ? null : $this->criticality->levelFromScore($score);
    }

    /**
     * @return array{probability:int, impact:int, score:int, criticality:string}
     */
    public function packageInherent(
        int|string|null $probability,
        int|string|null $impact,
        ?string $criticality = null,
    ): array {
        $normalizedProbability = $this->normalizeScaleValue($probability);
        $normalizedImpact = $this->normalizeScaleValue($impact);
        $normalizedCriticality = CriticalityLevel::fromMixed($criticality);

        if ($normalizedProbability === null || $normalizedImpact === null) {
            [$fallbackProbability, $fallbackImpact] = $this->fallbackCoordinates($normalizedCriticality);
            $normalizedProbability ??= $fallbackProbability;
            $normalizedImpact ??= $fallbackImpact;
        }

        $score = (int) $this->score($normalizedImpact, $normalizedProbability);
        $level = $this->criticality->levelFromScore($score);

        return [
            'probability' => $normalizedProbability,
            'impact' => $normalizedImpact,
            'score' => $score,
            'criticality' => $level->value,
        ];
    }

    /**
     * @return array{probability:int, impact:int, score:int, criticality:string}
     */
    public function packageResidualFromCoefficient(
        int $impactInherent,
        int $probabilityInherent,
        float $coefficient,
    ): array {
        $scoreInherent = max(1, min(25, $impactInherent * $probabilityInherent));
        $scoreResidual = max(1, min(25, (int) round($scoreInherent * $coefficient)));
        $impactResidual = $this->clampScale($impactInherent) ?? 1;
        $probabilityResidual = max(1, min(5, (int) ceil($scoreResidual / max(1, $impactResidual))));
        $level = $this->criticality->levelFromScore($scoreResidual);

        return [
            'probability' => $probabilityResidual,
            'impact' => $impactResidual,
            'score' => $scoreResidual,
            'criticality' => $level->value,
        ];
    }

    /**
     * @return array{int, int}
     */
    private function fallbackCoordinates(?CriticalityLevel $criticality): array
    {
        return match ($criticality) {
            CriticalityLevel::Faible => [2, 2],
            CriticalityLevel::Moyen => [3, 3],
            CriticalityLevel::Eleve => [4, 4],
            CriticalityLevel::Critique => [5, 4],
            null => [3, 3],
        };
    }
}
