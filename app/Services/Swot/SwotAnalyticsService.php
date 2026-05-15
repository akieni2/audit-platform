<?php

namespace App\Services\Swot;

use App\Models\MissionSwotPreview;
use App\Models\Department;
use App\Models\Mission;
use App\Models\SwotAnalysis;
use App\Models\SwotEntry;
use App\Models\SwotRecommendation;
use App\Models\SwotSnapshot;
use App\Models\SwotTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SwotAnalyticsService
{
    public function __construct(
        private SwotScoringService $scoring,
        private SwotAuditService $audit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function missionSnapshot(Mission $mission, ?SwotTemplate $template = null): array
    {
        $template ??= $this->resolveTemplateForMission($mission);
        $analyses = SwotAnalysis::query()
            ->where('mission_id', $mission->id)
            ->with(['swotTemplate', 'recommendations'])
            ->latest('id')
            ->get();

        return $this->composeSnapshot(
            scope: 'mission',
            label: $mission->organisation,
            template: $template,
            analyses: $analyses,
            recommendations: SwotRecommendation::query()->where('mission_id', $mission->id)->latest('priority_index')->get(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function departmentSnapshot(Department $department): array
    {
        $template = SwotTemplate::query()
            ->where(function ($query) use ($department) {
                $query->where('department_id', $department->id)
                    ->orWhere('is_global', true);
            })
            ->latest('updated_at')
            ->first();

        $analyses = SwotAnalysis::query()
            ->where('department_id', $department->id)
            ->with(['swotTemplate', 'recommendations'])
            ->latest('id')
            ->get();

        return $this->composeSnapshot(
            scope: 'department',
            label: $department->name,
            template: $template,
            analyses: $analyses,
            recommendations: SwotRecommendation::query()->where('department_id', $department->id)->latest('priority_index')->get(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function nationalSnapshot(): array
    {
        $template = SwotTemplate::query()
            ->where('is_global', true)
            ->latest('updated_at')
            ->first();

        return $this->composeSnapshot(
            scope: 'national',
            label: 'National',
            template: $template,
            analyses: SwotAnalysis::query()->with(['swotTemplate', 'recommendations'])->latest('id')->get(),
            recommendations: SwotRecommendation::query()->latest('priority_index')->get(),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function runMissionAnalysis(SwotTemplate $template, Mission $mission, array $payload = []): SwotAnalysis
    {
        return DB::transaction(function () use ($template, $mission, $payload) {
            $template->loadMissing(['entries.swotCategory']);
            $summary = $this->scoring->summarizeTemplate($template);

            $analysis = SwotAnalysis::query()->create([
                'swot_template_id' => $template->id,
                'mission_id' => $mission->id,
                'department_id' => $mission->department_id,
                'workflow_instance_id' => $payload['workflow_instance_id'] ?? null,
                'workflow_stage_execution_id' => $payload['workflow_stage_execution_id'] ?? null,
                'analysis_scope' => 'mission',
                'status' => $payload['status'] ?? 'completed',
                'score' => $summary['total_score'],
                'weighted_score' => $summary['priority_index'],
                'priority_index' => abs((float) $summary['strength_balance']),
                'analysis_payload' => [
                    'summary' => $summary,
                    'notes' => $payload['notes'] ?? null,
                ],
                'concluded_at' => now(),
                'created_by' => $payload['actor_id'] ?? null,
                'updated_by' => $payload['actor_id'] ?? null,
            ]);

            foreach ($template->entries->sortByDesc(fn (SwotEntry $entry) => $this->scoring->entryScore($entry))->take(6) as $entry) {
                SwotRecommendation::query()->create([
                    'swot_template_id' => $template->id,
                    'swot_analysis_id' => $analysis->id,
                    'swot_entry_id' => $entry->id,
                    'mission_id' => $mission->id,
                    'department_id' => $mission->department_id,
                    'title' => 'Action SWOT - '.$entry->title,
                    'description' => $entry->description ?: $entry->title,
                    'priority_level' => $entry->priority_level?->value ?? 'medium',
                    'priority_index' => $this->scoring->entryScore($entry),
                    'owner_role' => data_get($entry->metadata, 'owner_role'),
                    'status' => 'proposed',
                    'metadata' => [
                        'category_type' => $entry->swotCategory?->category_type?->value,
                    ],
                    'created_by' => $payload['actor_id'] ?? null,
                ]);
            }

            SwotSnapshot::query()->create([
                'swot_template_id' => $template->id,
                'swot_analysis_id' => $analysis->id,
                'mission_id' => $mission->id,
                'department_id' => $mission->department_id,
                'workflow_instance_id' => $payload['workflow_instance_id'] ?? null,
                'snapshot_hash' => sha1(json_encode($analysis->analysis_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: ''),
                'snapshot_payload' => [
                    'template' => [
                        'id' => $template->id,
                        'name' => $template->name,
                    ],
                    'summary' => $summary,
                ],
                'captured_at' => now(),
            ]);

            MissionSwotPreview::query()->create([
                'mission_id' => $mission->id,
                'status' => 'completed',
                'metadata' => [
                    'swot_analysis_id' => $analysis->id,
                    'swot_template_id' => $template->id,
                    'score' => $analysis->weighted_score,
                ],
            ]);

            $this->audit->log(
                eventName: 'swot.analysis.completed',
                analysis: $analysis,
                actor: isset($payload['actor_id']) ? \App\Models\User::query()->find($payload['actor_id']) : null,
                status: 'completed',
                payload: [
                    'template_id' => $template->id,
                    'mission_id' => $mission->id,
                ],
            );

            return $analysis->fresh(['recommendations', 'snapshots']);
        });
    }

    /**
     * @param  Collection<int, SwotAnalysis>  $analyses
     * @param  Collection<int, SwotRecommendation>  $recommendations
     * @return array<string, mixed>
     */
    private function composeSnapshot(
        string $scope,
        string $label,
        ?SwotTemplate $template,
        Collection $analyses,
        Collection $recommendations,
    ): array {
        $templateSummary = $template ? $this->scoring->summarizeTemplate($template) : [
            'quadrants' => [],
            'total_score' => 0,
            'priority_index' => 0,
            'strength_balance' => 0,
        ];

        return [
            'scope' => $scope,
            'label' => $label,
            'template' => $template,
            'summary' => $templateSummary,
            'analyses' => $analyses,
            'latest_analysis' => $analyses->first(),
            'recommendations' => $recommendations->take(10)->values(),
            'kpis' => [
                'analyses' => $analyses->count(),
                'recommendations' => $recommendations->count(),
                'total_score' => $templateSummary['total_score'],
                'priority_index' => $templateSummary['priority_index'],
            ],
            'timeline' => $analyses->take(10)->map(fn (SwotAnalysis $analysis) => [
                'title' => sprintf('Analyse %s', $analysis->analysis_scope),
                'message' => sprintf('Score %.2f - statut %s', (float) $analysis->weighted_score, (string) $analysis->status),
                'occurred_at' => $analysis->updated_at ?? $analysis->created_at,
            ])->values(),
        ];
    }

    private function resolveTemplateForMission(Mission $mission): ?SwotTemplate
    {
        return SwotTemplate::query()
            ->where(function ($query) use ($mission) {
                $query->where('department_id', $mission->department_id)
                    ->orWhere('is_global', true);
            })
            ->latest('updated_at')
            ->first();
    }
}
