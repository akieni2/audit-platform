<?php

namespace App\Services\Workflow;

use App\Models\IdentifiedRisk;
use App\Models\MissionDocument;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use App\Services\Workflow\Components\WorkflowStageComponentRegistry;
use Illuminate\Support\Facades\Schema;

class WorkflowStageUiRenderer
{
    public function __construct(
        private WorkflowStageComponentRegistry $components,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function render(WorkflowInstance $instance, WorkflowStage $stage, ?\App\Models\User $actor = null): array
    {
        $componentKey = $stage->resolvedComponentKey();
        $view = match ($componentKey) {
            'dynamic_form', 'dynamic_interview_form' => 'workflows.components.dynamic_form',
            'questionnaire_bridge' => 'workflows.components.questionnaire',
            'approval_form' => 'workflows.components.approval',
            'risk_capture_form' => 'workflows.components.risk_capture',
            default => match ($stage->resolvedStageType()?->value) {
                'heatmap' => 'workflows.components.heatmap',
                'reporting' => 'workflows.components.reporting',
                default => 'workflows.components.custom',
            },
        };

        $runtime = $this->components->resolve($stage)->buildViewData($instance, $stage, $actor);

        return [
            'view' => $view,
            'component_key' => $componentKey,
            'title' => $stage->name,
            'description' => $stage->description,
            'action_url' => route('workflow-runtime.stage', ['mission' => $instance->mission_id, 'stage' => $stage]),
            'runtime' => $runtime,
            'metrics' => $this->stageMetrics($instance, $stage),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stageMetrics(WorkflowInstance $instance, WorkflowStage $stage): array
    {
        return match ($stage->resolvedComponentKey()) {
            'risk_capture_form' => [
                'detected_risks' => Schema::hasTable('identified_risks')
                    ? IdentifiedRisk::query()->where('mission_id', $instance->mission_id)->count()
                    : 0,
            ],
            'dynamic_form', 'dynamic_interview_form' => [
                'form_submission_id' => data_get($instance->metadata, 'form_submissions.'.$stage->code),
            ],
            default => match ($stage->resolvedStageType()?->value) {
                'heatmap' => [
                    'documents' => Schema::hasTable('mission_documents')
                        ? MissionDocument::query()->where('mission_id', $instance->mission_id)->count()
                        : 0,
                ],
                default => [],
            },
        };
    }
}
