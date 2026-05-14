<?php

namespace App\Services\Governance;

use App\Models\ControlLibrary;
use App\Models\Department;
use App\Models\FormTemplate;
use App\Models\MethodologyTemplate;
use App\Models\Mission;
use App\Models\QuestionnaireTemplate;
use App\Models\Taxonomy;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTemplate;
use App\Models\User;
use App\Services\Intelligence\EnterpriseRiskIntelligenceService;

class ExecutiveAnalyticsService
{
    public function __construct(
        private ExecutiveDashboardService $executive,
        private EnterpriseRiskIntelligenceService $intelligence,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function nationalSnapshot(?User $actor = null): array
    {
        $filters = $this->filtersForActor($actor);
        $intelligence = $this->intelligence->snapshot($filters);

        return [
            'executive_kpis' => $this->executive->nationalKpis(),
            'intelligence' => $intelligence,
            'governance' => [
                'methodologies' => MethodologyTemplate::query()->count(),
                'taxonomies' => Taxonomy::query()->count(),
                'controls' => ControlLibrary::query()->count(),
                'workflow_templates' => WorkflowTemplate::query()->count(),
                'form_templates' => FormTemplate::query()->count(),
                'questionnaire_templates' => QuestionnaireTemplate::query()->count(),
                'active_workflows' => WorkflowInstance::query()->where('status', 'running')->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function departmentComparison(?User $actor = null): array
    {
        $filters = $this->filtersForActor($actor);
        $intelligence = $this->intelligence->snapshot($filters);

        return [
            'departments' => $intelligence['departments'],
            'missions' => Department::query()
                ->where('active', true)
                ->withCount('missions')
                ->orderBy('code')
                ->get()
                ->map(fn (Department $department) => [
                    'code' => $department->code,
                    'name' => $department->name,
                    'missions_count' => $department->missions_count,
                ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function maturityIndex(?User $actor = null): array
    {
        $comparison = $this->departmentComparison($actor);

        return [
            'departments' => collect($comparison['departments'])->map(function (array $entry) {
                $base = max(1, (int) ($entry['registry_count'] + $entry['intake_count']));
                $score = max(0, min(100, (int) round((($entry['registry_count'] * 100) / $base) - ($entry['critical_open'] * 3))));

                return [
                    ...$entry,
                    'maturity_score' => $score,
                ];
            })->sortByDesc('maturity_score')->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function governanceOverview(?User $actor = null): array
    {
        $filters = $this->filtersForActor($actor);

        return [
            'national_missions' => Mission::query()
                ->when(isset($filters['department_id']), fn ($query) => $query->where('department_id', $filters['department_id']))
                ->count(),
            'department_defaults' => Department::query()->whereNotNull('default_methodology_template_id')->orWhereNotNull('default_taxonomy_id')->count(),
            'global_workflows' => WorkflowTemplate::query()->where('is_global_template', true)->count(),
            'private_workflows' => WorkflowTemplate::query()->where('is_private_template', true)->count(),
            'global_forms' => FormTemplate::query()->where('is_global_template', true)->count(),
            'global_questionnaires' => QuestionnaireTemplate::query()->where('is_global_template', true)->count(),
            'intelligence' => $this->intelligence->snapshot($filters),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function riskIntelligence(?User $actor = null): array
    {
        return $this->intelligence->snapshot($this->filtersForActor($actor));
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersForActor(?User $actor): array
    {
        if (! $actor instanceof User) {
            return [];
        }

        if (method_exists($actor, 'canViewAllInstitutionalData') && $actor->canViewAllInstitutionalData()) {
            return [];
        }

        return $actor->department_id !== null
            ? ['department_id' => $actor->department_id]
            : [];
    }
}
