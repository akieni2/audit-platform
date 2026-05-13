<?php

namespace App\Services\Risk;

use App\Domain\Risk\Enums\CriticalityLevel;

/**
 * Matrice de criticité : score = probabilité × impact (échelle 1–25).
 * Seuils : Faible ≤6, Moyen 7–12, Élevé 13–18, Critique ≥19.
 */
final class CriticalityEvaluationService
{
    public const SCORE_MIN = 1;

    public const SCORE_MAX = 25;

    public function levelFromScore(int $score): CriticalityLevel
    {
        $score = max(self::SCORE_MIN, min(self::SCORE_MAX, $score));

        return match (true) {
            $score <= 6 => CriticalityLevel::Low,
            $score <= 12 => CriticalityLevel::Medium,
            $score <= 18 => CriticalityLevel::High,
            default => CriticalityLevel::Critical,
        };
    }

    /**
     * Couleur heatmap (axe impact × probabilité) : vert → jaune → orange → rouge.
     *
     * @return 'green'|'yellow'|'orange'|'red'
     */
    public function heatmapTintForCoordinates(int $impact, int $probability): string
    {
        $score = max(1, min(25, $impact * $probability));

        return match ($this->levelFromScore($score)) {
            CriticalityLevel::Low => 'green',
            CriticalityLevel::Medium => 'yellow',
            CriticalityLevel::High => 'orange',
            CriticalityLevel::Critical => 'red',
        };
    }

    /** Classes Tailwind pour fond + texte lisible */
    public function heatmapCellClasses(CriticalityLevel $level): string
    {
        return match ($level) {
            CriticalityLevel::Low => 'bg-green-500 text-white',
            CriticalityLevel::Medium => 'bg-yellow-400 text-slate-900',
            CriticalityLevel::High => 'bg-orange-500 text-white',
            CriticalityLevel::Critical => 'bg-red-600 text-white',
        };
    }
}
