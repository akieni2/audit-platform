<?php

namespace App\Services\Workflow;

use App\Domain\Workflow\Enums\WorkflowStageType;
use App\Domain\Workflow\Enums\WorkflowStageExecutionStatus;
use App\Domain\Workflow\Enums\WorkflowExecutionMode;
use App\Domain\Workflow\Enums\WorkflowTemplateStatus;
use App\Models\Mission;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use App\Models\WorkflowTemplate;
use App\Models\WorkflowTransition;
use App\Services\Runtime\BusinessEventLogger;
use App\Services\Runtime\CoreTransactionRunner;
use Illuminate\Support\Facades\Schema;

class WorkflowCompatibilityService
{
    public const DEFAULT_TEMPLATE_CODE = 'DEFAULT_DGCPT_WORKFLOW';

    public function __construct(
        private WorkflowEngineService $engine,
        private WorkflowExecutionService $execution,
        private CoreTransactionRunner $transactions,
        private BusinessEventLogger $events,
    ) {}

    public function ensureDefaultTemplate(?User $actor = null): WorkflowTemplate
    {
        if (! $this->isAvailable()) {
            return new WorkflowTemplate([
                'name' => 'Workflow DGCPT par défaut',
                'code' => self::DEFAULT_TEMPLATE_CODE,
                'active' => true,
                'is_system' => true,
                'version' => 1,
                'status' => WorkflowTemplateStatus::Published->value,
            ]);
        }

        $template = WorkflowTemplate::query()
            ->where('code', self::DEFAULT_TEMPLATE_CODE)
            ->with(['stages', 'transitions'])
            ->first();

        if ($template !== null) {
            return $template;
        }

        /** @var WorkflowTemplate $template */
        $template = $this->transactions->run(
            name: 'workflow.template.bootstrap_default',
            context: ['workflow_template_code' => self::DEFAULT_TEMPLATE_CODE],
            callback: function () use ($actor) {
                $template = WorkflowTemplate::query()->create([
                    'department_id' => null,
                    'name' => 'Workflow DGCPT par défaut',
                    'slug' => 'default-dgcpt-workflow',
                    'description' => 'Couche dynamique additive compatible avec le parcours mission -> services -> entretiens -> risques -> cartographie -> actions -> rapports.',
                    'code' => self::DEFAULT_TEMPLATE_CODE,
                    'active' => true,
                    'is_system' => true,
                    'version' => 1,
                    'status' => WorkflowTemplateStatus::Published->value,
                    'created_by' => $actor?->id,
                    'updated_by' => $actor?->id,
                    'published_at' => now(),
                ]);

                $stages = [];
                foreach ($this->defaultStageDefinitions() as $definition) {
                    $stages[$definition['code']] = WorkflowStage::query()->create([
                        'workflow_template_id' => $template->id,
                        ...$definition,
                    ]);
                }

                foreach ($this->defaultTransitionDefinitions() as $definition) {
                    WorkflowTransition::query()->create([
                        'workflow_template_id' => $template->id,
                        'from_stage_id' => $stages[$definition['from']]->id,
                        'to_stage_id' => $stages[$definition['to']]->id,
                        'condition_type' => $definition['condition_type'] ?? null,
                        'condition_configuration' => $definition['condition_configuration'] ?? null,
                        'role_required' => $definition['role_required'] ?? null,
                        'is_automatic' => $definition['is_automatic'] ?? false,
                    ]);
                }

                return $template->fresh(['stages', 'transitions']);
            }
        );

        $this->events->record(
            eventName: 'workflow.template.default_bootstrapped',
            payload: [
                'workflow_template_id' => $template->id,
                'code' => $template->code,
                'version' => $template->version,
            ],
            aggregateType: 'workflow_template',
            aggregateId: $template->id,
            actor: $actor,
            idempotencyKey: 'workflow-template-default:'.$template->code.':'.$template->version,
        );

        return $template;
    }

    public function ensureMissionWorkflow(Mission $mission, ?User $actor = null): WorkflowInstance
    {
        if (! $this->isAvailable()) {
            return new WorkflowInstance([
                'mission_id' => $mission->id,
                'status' => 'draft',
                'metadata' => ['compatibility_mode' => true],
            ]);
        }

        $mission->loadMissing('workflowInstance.workflowTemplate.stages', 'workflowInstance.stageExecutions.workflowStage');

        if ($mission->workflowInstance !== null) {
            return $mission->workflowInstance;
        }

        $template = $this->resolveTemplateForMission($mission, $actor);

        return $this->execution->startWorkflow(
            mission: $mission,
            template: $template,
            actor: $actor,
            metadata: [
                'compatibility_mode' => true,
                'system_template_code' => $template->code ?: self::DEFAULT_TEMPLATE_CODE,
            ],
        );
    }

    public function syncMissionWorkflow(Mission $mission, ?User $actor = null): WorkflowInstance
    {
        if (! $this->isAvailable()) {
            return new WorkflowInstance([
                'mission_id' => $mission->id,
                'status' => 'draft',
                'metadata' => ['compatibility_mode' => true],
            ]);
        }

        return $this->execution->syncInstance($this->ensureMissionWorkflow($mission, $actor), $actor);
    }

    /**
     * @return array<string, mixed>
     */
    public function workflowViewData(Mission $mission, ?User $actor = null): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $mission->loadMissing(
            'workflowInstance.workflowTemplate',
            'workflowInstance.currentStage.formTemplate',
            'workflowInstance.currentStage.questionnaireTemplate',
            'workflowInstance.stageExecutions.workflowStage',
            'workflowInstance.executionLogs',
            'workflowInstance.formSubmissions'
        );
        if (! $mission->workflowInstance instanceof WorkflowInstance) {
            return [];
        }

        $instance = $mission->workflowInstance->fresh([
            'workflowTemplate',
            'currentStage.formTemplate',
            'currentStage.questionnaireTemplate',
            'stageExecutions.workflowStage',
            'executionLogs',
            'formSubmissions',
        ]);

        $executions = $instance->stageExecutions
            ->sortBy(fn ($execution) => $execution->workflowStage?->sort_order ?? PHP_INT_MAX)
            ->values();

        return [
            'instance' => $instance,
            'currentStage' => $instance->currentStage,
            'currentStageRuntimeUrl' => $instance->currentStage
                ? route('workflow-runtime.stage', ['mission' => $mission, 'stage' => $instance->currentStage])
                : null,
            'currentStageComponentKey' => $instance->currentStage?->resolvedComponentKey(),
            'currentStageSubmission' => $instance->currentStage
                ? $instance->formSubmissions->where('workflow_stage_id', $instance->currentStage->id)->sortByDesc('id')->first()
                : null,
            'executions' => $executions,
            'completedCount' => $executions->filter(function ($execution) {
                $status = $execution->status instanceof WorkflowStageExecutionStatus
                    ? $execution->status
                    : WorkflowStageExecutionStatus::from((string) $execution->status);

                return $status === WorkflowStageExecutionStatus::Completed;
            })->count(),
            'totalCount' => $executions->count(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaultStageDefinitions(): array
    {
        return [
            [
                'name' => 'Mission',
                'code' => 'mission',
                'description' => 'Cadre et contexte de mission.',
                'stage_type' => WorkflowStageType::Mission->value,
                'execution_mode' => WorkflowExecutionMode::Automatic->value,
                'ui_component' => 'stage-card',
                'configuration_json' => ['module' => 'mission'],
                'sort_order' => 10,
                'configuration' => ['module' => 'mission'],
                'position_x' => 0,
                'position_y' => 0,
                'color' => '#0A2A66',
                'icon' => 'mission',
                'is_required' => true,
                'is_repeatable' => false,
                'role_scope' => null,
            ],
            [
                'name' => 'Services',
                'code' => 'services',
                'description' => 'Sélection et structuration des services audités.',
                'stage_type' => WorkflowStageType::ServiceSelection->value,
                'execution_mode' => WorkflowExecutionMode::Automatic->value,
                'ui_component' => 'stage-card',
                'configuration_json' => ['module' => 'services'],
                'sort_order' => 20,
                'configuration' => ['module' => 'services'],
                'position_x' => 240,
                'position_y' => 0,
                'color' => '#005A8C',
                'icon' => 'services',
                'is_required' => true,
                'is_repeatable' => false,
                'role_scope' => null,
            ],
            [
                'name' => 'Entretiens',
                'code' => 'entretiens',
                'description' => 'Conduite des entretiens et collecte runtime.',
                'stage_type' => WorkflowStageType::Questionnaire->value,
                'execution_mode' => WorkflowExecutionMode::Questionnaire->value,
                'ui_component' => 'questionnaire-stage',
                'configuration_json' => ['module' => 'entretiens'],
                'sort_order' => 30,
                'configuration' => ['module' => 'entretiens'],
                'position_x' => 480,
                'position_y' => 0,
                'color' => '#0E7490',
                'icon' => 'questionnaire',
                'is_required' => true,
                'is_repeatable' => true,
                'role_scope' => null,
            ],
            [
                'name' => 'Risques',
                'code' => 'risques',
                'description' => 'Identification des risques issus du terrain.',
                'stage_type' => WorkflowStageType::RiskCapture->value,
                'execution_mode' => WorkflowExecutionMode::Automatic->value,
                'ui_component' => 'risk-stage',
                'configuration_json' => ['module' => 'risques'],
                'sort_order' => 40,
                'configuration' => ['module' => 'risques'],
                'position_x' => 720,
                'position_y' => 0,
                'color' => '#7C3AED',
                'icon' => 'risk',
                'is_required' => true,
                'is_repeatable' => true,
                'role_scope' => null,
            ],
            [
                'name' => 'Cartographie',
                'code' => 'cartographie',
                'description' => 'Projection heatmap et visualisation consolidée.',
                'stage_type' => WorkflowStageType::Heatmap->value,
                'execution_mode' => WorkflowExecutionMode::Automatic->value,
                'ui_component' => 'heatmap-stage',
                'configuration_json' => ['module' => 'cartographie'],
                'sort_order' => 50,
                'configuration' => ['module' => 'cartographie'],
                'position_x' => 960,
                'position_y' => 0,
                'color' => '#D97706',
                'icon' => 'heatmap',
                'is_required' => true,
                'is_repeatable' => false,
                'role_scope' => null,
            ],
            [
                'name' => 'Actions',
                'code' => 'actions',
                'description' => 'Plan d’action et suivi correctif.',
                'stage_type' => WorkflowStageType::ActionPlan->value,
                'execution_mode' => WorkflowExecutionMode::Manual->value,
                'ui_component' => 'action-stage',
                'configuration_json' => ['module' => 'actions'],
                'sort_order' => 60,
                'configuration' => ['module' => 'actions'],
                'position_x' => 1200,
                'position_y' => 0,
                'color' => '#047857',
                'icon' => 'action-plan',
                'is_required' => true,
                'is_repeatable' => true,
                'role_scope' => null,
            ],
            [
                'name' => 'Rapports',
                'code' => 'rapports',
                'description' => 'Production et publication des rapports.',
                'stage_type' => WorkflowStageType::Reporting->value,
                'execution_mode' => WorkflowExecutionMode::Manual->value,
                'ui_component' => 'reporting-stage',
                'configuration_json' => ['module' => 'rapports'],
                'sort_order' => 70,
                'configuration' => ['module' => 'rapports'],
                'position_x' => 1440,
                'position_y' => 0,
                'color' => '#1D4ED8',
                'icon' => 'report',
                'is_required' => true,
                'is_repeatable' => false,
                'role_scope' => null,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaultTransitionDefinitions(): array
    {
        return [
            ['from' => 'mission', 'to' => 'services', 'is_automatic' => true],
            ['from' => 'services', 'to' => 'entretiens', 'is_automatic' => true],
            ['from' => 'entretiens', 'to' => 'risques', 'is_automatic' => true],
            ['from' => 'risques', 'to' => 'cartographie', 'is_automatic' => true],
            ['from' => 'cartographie', 'to' => 'actions', 'is_automatic' => true],
            ['from' => 'actions', 'to' => 'rapports', 'is_automatic' => true],
        ];
    }

    private function isAvailable(): bool
    {
        return Schema::hasTable('workflow_templates')
            && Schema::hasTable('workflow_stages')
            && Schema::hasTable('workflow_transitions')
            && Schema::hasTable('workflow_instances')
            && Schema::hasTable('workflow_stage_executions')
            && Schema::hasColumn('missions', 'workflow_instance_id');
    }

    private function resolveTemplateForMission(Mission $mission, ?User $actor = null): WorkflowTemplate
    {
        if (! $this->isAvailable()) {
            return $this->ensureDefaultTemplate($actor);
        }

        $departmentTemplate = WorkflowTemplate::query()
            ->where('active', true)
            ->where('status', WorkflowTemplateStatus::Published->value)
            ->where('department_id', $mission->department_id)
            ->orderByDesc('version')
            ->first();

        if ($departmentTemplate instanceof WorkflowTemplate) {
            return $departmentTemplate->loadMissing(['stages', 'transitions']);
        }

        return $this->ensureDefaultTemplate($actor);
    }
}
