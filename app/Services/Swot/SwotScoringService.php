<?php

namespace App\Services\Swot;

use App\Domain\Swot\Enums\SwotCategoryType;
use App\Domain\Swot\Enums\SwotImpactLevel;
use App\Domain\Swot\Enums\SwotPriorityLevel;
use App\Models\SwotEntry;
use App\Models\SwotTemplate;
use Illuminate\Support\Collection;

class SwotScoringService
{
    /**
     * @return array<string, mixed>
     */
    public function summarizeTemplate(SwotTemplate $template): array
    {
        $template->loadMissing('entries.swotCategory');

        return $this->summarizeEntries($template->entries);
    }

    /**
     * @param  Collection<int, SwotEntry>  $entries
     * @return array<string, mixed>
     */
    public function summarizeEntries(Collection $entries): array
    {
        $grouped = $entries
            ->filter(fn (SwotEntry $entry) => $entry->is_active)
            ->groupBy(function (SwotEntry $entry) {
                return $entry->swotCategory?->category_type?->value
                    ?? SwotCategoryType::Strength->value;
            });

        $quadrants = [];
        foreach (SwotCategoryType::cases() as $type) {
            $quadrantEntries = $grouped->get($type->value, collect());
            $score = round($quadrantEntries->sum(fn (SwotEntry $entry) => $this->entryScore($entry)), 2);

            $quadrants[$type->value] = [
                'label' => $type->label(),
                'count' => $quadrantEntries->count(),
                'score' => $score,
                'entries' => $quadrantEntries->values(),
            ];
        }

        $total = round(collect($quadrants)->sum('score'), 2);

        return [
            'quadrants' => $quadrants,
            'total_score' => $total,
            'priority_index' => round(collect($quadrants)->avg('score') ?: 0, 2),
            'strength_balance' => round(($quadrants['strength']['score'] + $quadrants['opportunity']['score']) - ($quadrants['weakness']['score'] + $quadrants['threat']['score']), 2),
        ];
    }

    public function entryScore(SwotEntry $entry): float
    {
        $impact = $entry->impact_level instanceof SwotImpactLevel ? $entry->impact_level->score() : 0;
        $priority = $entry->priority_level instanceof SwotPriorityLevel ? $entry->priority_level->weight() : 0;
        $weight = (float) ($entry->weight ?? 1);

        return round(($impact + $priority) * $weight, 2);
    }
}
