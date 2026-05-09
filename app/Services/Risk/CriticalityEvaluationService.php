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
            $score <= 6 => CriticalityLevel::Faible,
            $score <= 12 => CriticalityLevel::Moyen,
            $score <= 18 => CriticalityLevel::Eleve,
            default => CriticalityLevel::Critique,
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
            CriticalityLevel::Faible => 'green',
            CriticalityLevel::Moyen => 'yellow',
            CriticalityLevel::Eleve => 'orange',
            CriticalityLevel::Critique => 'red',
        };
    }

    /** Classes Tailwind pour fond + texte lisible */
    public function heatmapCellClasses(CriticalityLevel $level): string
    {
        return match ($level) {
            CriticalityLevel::Faible => 'bg-green-500 text-white',
            CriticalityLevel::Moyen => 'bg-yellow-400 text-slate-900',
            CriticalityLevel::Eleve => 'bg-orange-500 text-white',
            CriticalityLevel::Critique => 'bg-red-600 text-white',
        };
    }
}
