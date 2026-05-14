<?php

namespace App\Services\Workflow;

use App\Domain\Workflow\Enums\WorkflowStageType;
use App\Domain\Workflow\Enums\WorkflowStageExecutionStatus;
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

        $template = $this->ensureDefaultTemplate($actor);

        return $this->engine->start(
            mission: $mission,
            template: $template,
            actor: $actor,
            metadata: [
                'compatibility_mode' => true,
                'system_template_code' => self::DEFAULT_TEMPLATE_CODE,
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

        return $this->engine->synchronize($this->ensureMissionWorkflow($mission, $actor), $actor);
    }

    /**
     * @return array<string, mixed>
     */
    public function workflowViewData(Mission $mission, ?User $actor = null): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $instance = $this->syncMissionWorkflow($mission, $actor)->fresh([
            'workflowTemplate',
            'currentStage',
            'stageExecutions.workflowStage',
        ]);

        $executions = $instance->stageExecutions
            ->sortBy(fn ($execution) => $execution->workflowStage?->sort_order ?? PHP_INT_MAX)
            ->values();

        return [
            'instance' => $instance,
            'currentStage' => $instance->currentStage,
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
                'stage_type' => WorkflowStageType::MissionContext->value,
                'sort_order' => 10,
                'configuration' => ['module' => 'mission'],
                'is_required' => true,
                'is_repeatable' => false,
                'role_scope' => null,
            ],
            [
                'name' => 'Services',
                'code' => 'services',
                'description' => 'Sélection et structuration des services audités.',
                'stage_type' => WorkflowStageType::ServiceSelection->value,
                'sort_order' => 20,
                'configuration' => ['module' => 'services'],
                'is_required' => true,
                'is_repeatable' => false,
                'role_scope' => null,
            ],
            [
                'name' => 'Entretiens',
                'code' => 'entretiens',
                'description' => 'Conduite des entretiens et collecte runtime.',
                'stage_type' => WorkflowStageType::Entretien->value,
                'sort_order' => 30,
                'configuration' => ['module' => 'entretiens'],
                'is_required' => true,
                'is_repeatable' => true,
                'role_scope' => null,
            ],
            [
                'name' => 'Risques',
                'code' => 'risques',
                'description' => 'Identification des risques issus du terrain.',
                'stage_type' => WorkflowStageType::RiskIdentification->value,
                'sort_order' => 40,
                'configuration' => ['module' => 'risques'],
                'is_required' => true,
                'is_repeatable' => true,
                'role_scope' => null,
            ],
            [
                'name' => 'Cartographie',
                'code' => 'cartographie',
                'description' => 'Projection heatmap et visualisation consolidée.',
                'stage_type' => WorkflowStageType::Heatmap->value,
                'sort_order' => 50,
                'configuration' => ['module' => 'cartographie'],
                'is_required' => true,
                'is_repeatable' => false,
                'role_scope' => null,
            ],
            [
                'name' => 'Actions',
                'code' => 'actions',
                'description' => 'Plan d’action et suivi correctif.',
                'stage_type' => WorkflowStageType::ActionPlan->value,
                'sort_order' => 60,
                'configuration' => ['module' => 'actions'],
                'is_required' => true,
                'is_repeatable' => true,
                'role_scope' => null,
            ],
            [
                'name' => 'Rapports',
                'code' => 'rapports',
                'description' => 'Production et publication des rapports.',
                'stage_type' => WorkflowStageType::Reporting->value,
                'sort_order' => 70,
                'configuration' => ['module' => 'rapports'],
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
}
