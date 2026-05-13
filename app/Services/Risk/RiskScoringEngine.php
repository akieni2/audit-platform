<?php

namespace App\Services\Risk;

use App\Domain\Risk\Enums\CriticalityLevel;

class RiskScoringEngine
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

        $normalized = strtolower(trim((string) $value));
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

    /**
     * @return array{probability:int, impact:int, score:int, criticality:string, heatmap_x:int, heatmap_y:int}
     */
    public function inherent(
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
        [$x, $y] = $this->heatmapPosition($normalizedImpact, $normalizedProbability);

        return [
            'probability' => $normalizedProbability,
            'impact' => $normalizedImpact,
            'score' => $score,
            'criticality' => $level->value,
            'heatmap_x' => $x,
            'heatmap_y' => $y,
        ];
    }

    /**
     * @return array{probability:int, impact:int, score:int, criticality:string, heatmap_x:int, heatmap_y:int}
     */
    public function residual(
        int|string|null $probability,
        int|string|null $impact,
        int $controls = 0,
        int|float|null $mitigation = null,
        ?string $criticality = null,
    ): array {
        $base = $this->inherent($probability, $impact, $criticality);

        $controlsReduction = min(0.6, max(0, $controls) * 0.1);
        $mitigationReduction = min(0.7, max(0.0, min(1.0, (float) ($mitigation ?? 0))) * 0.5);
        $coefficient = max(0.2, 1 - $controlsReduction - $mitigationReduction);

        return $this->residualFromCoefficient(
            impactInherent: $base['impact'],
            probabilityInherent: $base['probability'],
            coefficient: $coefficient,
        );
    }

    /**
     * @return array{probability:int, impact:int, score:int, criticality:string, heatmap_x:int, heatmap_y:int}
     */
    public function residualFromCoefficient(
        int $impactInherent,
        int $probabilityInherent,
        float $coefficient,
    ): array {
        $scoreInherent = max(1, min(25, $impactInherent * $probabilityInherent));
        $scoreResidual = max(1, min(25, (int) round($scoreInherent * $coefficient)));
        $impactResidual = $this->clampScale($impactInherent) ?? 1;
        $probabilityResidual = max(1, min(5, (int) ceil($scoreResidual / max(1, $impactResidual))));
        $level = $this->criticality->levelFromScore($scoreResidual);
        [$x, $y] = $this->heatmapPosition($impactResidual, $probabilityResidual);

        return [
            'probability' => $probabilityResidual,
            'impact' => $impactResidual,
            'score' => $scoreResidual,
            'criticality' => $level->value,
            'heatmap_x' => $x,
            'heatmap_y' => $y,
        ];
    }

    /**
     * @return array{0:int, 1:int}
     */
    public function heatmapPosition(int $impact, int $probability): array
    {
        return [
            max(1, min(5, $impact)),
            max(1, min(5, $probability)),
        ];
    }

    public function canonicalCriticality(?string $value): ?string
    {
        return CriticalityLevel::fromMixed($value)?->value;
    }

    /**
     * @return array{int, int}
     */
    private function fallbackCoordinates(?CriticalityLevel $criticality): array
    {
        return match ($criticality) {
            CriticalityLevel::Low => [2, 2],
            CriticalityLevel::Medium => [3, 3],
            CriticalityLevel::High => [4, 4],
            CriticalityLevel::Critical => [5, 4],
            null => [3, 3],
        };
    }
}
