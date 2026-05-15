<?php

namespace App\Services\Workflow;

use App\Models\WorkflowTemplate;
use Illuminate\Support\Collection;

class WorkflowGraphLayoutService
{
    /**
     * @param  Collection<int, array<string, mixed>>  $nodes
     * @return array<string, mixed>
     */
    public function describe(WorkflowTemplate $template, Collection $nodes): array
    {
        $nodes = $nodes->values();
        $maxX = (int) $nodes->max('x');
        $maxY = (int) $nodes->max('y');

        $lanes = $nodes
            ->groupBy('lane')
            ->map(function (Collection $items, string $lane) {
                return [
                    'key' => $lane,
                    'label' => $lane === 'default' ? 'Flow principal' : $lane,
                    'count' => $items->count(),
                    'items' => $items->pluck('id')->all(),
                ];
            })
            ->values();

        return [
            'template_id' => (int) $template->id,
            'width' => max(1440, $maxX + 420),
            'height' => max(760, $maxY + 260),
            'zoom' => 1,
            'lanes' => $lanes->all(),
            'minimap' => [
                'width' => 220,
                'height' => 140,
                'ratio_x' => max(1, $maxX + 320),
                'ratio_y' => max(1, $maxY + 220),
            ],
        ];
    }

    public function laneForStage(\App\Models\WorkflowStage $stage): string
    {
        if ($stage->requires_approval) {
            return 'Lane Approval';
        }

        $stageType = strtolower((string) ($stage->resolvedStageType()?->value ?? ''));

        return match ($stageType) {
            'mission', 'mission_context', 'service_selection' => 'Lane Intake',
            'questionnaire', 'form' => 'Lane Evidence',
                    'swot_analysis', 'swot_validation' => 'Lane SWOT',
                    'raci_assignment', 'raci_validation' => 'Lane RACI',
            'risk_capture', 'risk_identification', 'risk_review', 'risk_validation', 'heatmap' => 'Lane Risk',
            'reporting', 'archive' => 'Lane Closure',
            default => 'Flow principal',
        };
    }
}
