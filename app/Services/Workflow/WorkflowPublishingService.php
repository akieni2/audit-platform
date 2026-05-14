<?php

namespace App\Services\Workflow;

use App\Domain\Workflow\Enums\WorkflowTemplateStatus;
use App\Models\FormTemplate;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Models\WorkflowTemplate;
use App\Models\WorkflowTransition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WorkflowPublishingService
{
    public function ensureEditableDraft(WorkflowTemplate $template, ?User $actor = null): WorkflowTemplate
    {
        if (! $template->isImmutable()) {
            return $template;
        }

        $rootId = $this->lineageRootId($template);

        $existingDraft = WorkflowTemplate::query()
            ->where(function ($query) use ($rootId) {
                $query->whereKey($rootId)
                    ->orWhere('source_template_id', $rootId);
            })
            ->where('status', WorkflowTemplateStatus::Draft->value)
            ->orderByDesc('version')
            ->first();

        if ($existingDraft instanceof WorkflowTemplate) {
            return $existingDraft;
        }

        return DB::transaction(function () use ($template, $actor, $rootId) {
            $template->loadMissing(['stages', 'transitions']);

            $draft = WorkflowTemplate::query()->create([
                'department_id' => $template->department_id,
                'name' => $template->name,
                'slug' => $this->uniqueDraftSlug($template, $rootId),
                'description' => $template->description,
                'code' => $template->code,
                'active' => false,
                'is_system' => $template->is_system,
                'version' => $this->nextVersionNumber($rootId),
                'status' => WorkflowTemplateStatus::Draft->value,
                'signature_hash' => null,
                'published_at' => null,
                'deprecated_at' => null,
                'archived_at' => null,
                'source_template_id' => $rootId,
                'created_by' => $actor?->id ?? $template->created_by,
                'updated_by' => $actor?->id ?? $template->updated_by,
            ]);

            $stageMap = [];
            foreach ($template->stages as $stage) {
                $draftStage = WorkflowStage::query()->create([
                    'workflow_template_id' => $draft->id,
                    'name' => $stage->name,
                    'code' => $stage->code,
                    'description' => $stage->description,
                    'stage_type' => $stage->resolvedStageType()?->value,
                    'ui_component' => $stage->ui_component,
                    'configuration' => $stage->configuration,
                    'configuration_json' => $stage->resolvedConfiguration(),
                    'validation_rules_json' => $stage->resolvedValidationRules(),
                    'execution_mode' => $stage->resolvedExecutionMode()?->value,
                    'allow_skip' => (bool) $stage->allow_skip,
                    'requires_approval' => (bool) $stage->requires_approval,
                    'approval_role_id' => $stage->approval_role_id,
                    'questionnaire_template_id' => $stage->questionnaire_template_id,
                    'form_template_id' => $stage->form_template_id,
                    'component_key' => $stage->component_key,
                    'form_schema_json' => $stage->resolvedFormSchema(),
                    'risk_matrix_schema_json' => $stage->resolvedRiskMatrixSchema(),
                    'position_x' => $stage->position_x,
                    'position_y' => $stage->position_y,
                    'color' => $stage->color,
                    'icon' => $stage->icon,
                    'sort_order' => $stage->sort_order,
                    'is_required' => $stage->is_required,
                    'is_repeatable' => $stage->is_repeatable,
                    'role_scope' => $stage->role_scope,
                ]);

                $stageMap[(int) $stage->id] = (int) $draftStage->id;
            }

            foreach ($template->transitions as $transition) {
                WorkflowTransition::query()->create([
                    'workflow_template_id' => $draft->id,
                    'from_stage_id' => $stageMap[(int) $transition->from_stage_id] ?? null,
                    'to_stage_id' => $stageMap[(int) $transition->to_stage_id] ?? null,
                    'condition_type' => $transition->condition_type,
                    'condition_configuration' => $transition->condition_configuration,
                    'role_required' => $transition->role_required,
                    'is_automatic' => $transition->is_automatic,
                ]);
            }

            return $draft->fresh(['stages', 'transitions']);
        });
    }

    public function publish(WorkflowTemplate $template, ?User $actor = null): WorkflowTemplate
    {
        $template->loadMissing(['stages', 'transitions']);
        $this->validateStructure($template);

        $rootId = $this->lineageRootId($template);
        $signature = $this->signatureFor($template);
        $version = max((int) ($template->version ?? 1), $this->nextVersionNumber($rootId, $template->id));

        return DB::transaction(function () use ($template, $actor, $rootId, $signature, $version) {
            WorkflowTemplate::query()
                ->where(function ($query) use ($rootId) {
                    $query->whereKey($rootId)
                        ->orWhere('source_template_id', $rootId);
                })
                ->where('status', WorkflowTemplateStatus::Published->value)
                ->whereKeyNot($template->id)
                ->update([
                    'status' => WorkflowTemplateStatus::Deprecated->value,
                    'active' => false,
                    'deprecated_at' => now(),
                    'updated_at' => now(),
                ]);

            $template->forceFill([
                'version' => $version,
                'active' => true,
                'status' => WorkflowTemplateStatus::Published->value,
                'signature_hash' => $signature,
                'published_at' => now(),
                'deprecated_at' => null,
                'archived_at' => null,
                'source_template_id' => $rootId === $template->id ? null : $rootId,
                'updated_by' => $actor?->id ?? $template->updated_by,
            ])->save();

            return $template->fresh(['stages', 'transitions']);
        });
    }

    public function archive(WorkflowTemplate $template, ?User $actor = null): WorkflowTemplate
    {
        $template->forceFill([
            'active' => false,
            'status' => WorkflowTemplateStatus::Archived->value,
            'archived_at' => now(),
            'updated_by' => $actor?->id ?? $template->updated_by,
        ])->save();

        return $template->fresh();
    }

    public function validateStructure(WorkflowTemplate $template): void
    {
        $template->loadMissing(['stages', 'transitions']);
        $stages = $template->stages->sortBy('sort_order')->values();

        if ($stages->isEmpty()) {
            throw new InvalidArgumentException('Le workflow doit contenir au moins une étape avant publication.');
        }

        foreach ($stages as $stage) {
            if (blank($stage->name) || blank($stage->code)) {
                throw new InvalidArgumentException('Chaque étape doit définir un nom et un code.');
            }

            if ($stage->requires_approval && $stage->approval_role_id === null) {
                throw new InvalidArgumentException(sprintf(
                    'L’étape "%s" requiert un rôle d’approbation.',
                    $stage->name
                ));
            }

            if ($stage->usesQuestionnaire() && $stage->questionnaireTemplate === null) {
                throw new InvalidArgumentException(sprintf(
                    'L’étape "%s" référence un questionnaire invalide.',
                    $stage->name
                ));
            }

            if ($stage->usesQuestionnaire() && ! $stage->questionnaireTemplate?->active) {
                throw new InvalidArgumentException(sprintf(
                    'L’étape "%s" doit référencer un questionnaire publié actif.',
                    $stage->name
                ));
            }

            if ($stage->form_template_id !== null && ! FormTemplate::query()->whereKey($stage->form_template_id)->where('active', true)->exists()) {
                throw new InvalidArgumentException(sprintf(
                    'L’étape "%s" doit référencer un formulaire publié actif.',
                    $stage->name
                ));
            }
        }

        if ($stages->count() > 1 && $template->transitions->isEmpty()) {
            throw new InvalidArgumentException('Le workflow doit contenir au moins une transition.');
        }

        $stageIds = $stages->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($template->transitions as $transition) {
            if (! in_array((int) $transition->from_stage_id, $stageIds, true)
                || ! in_array((int) $transition->to_stage_id, $stageIds, true)) {
                throw new InvalidArgumentException('Une transition référence une étape hors du workflow.');
            }
        }
    }

    public function signatureFor(WorkflowTemplate $template): string
    {
        $template->loadMissing(['stages', 'transitions.fromStage', 'transitions.toStage']);

        $payload = [
            'template' => [
                'name' => (string) $template->name,
                'slug' => (string) $template->slug,
                'description' => $template->description,
                'department_id' => $template->department_id,
                'code' => $template->code,
                'version' => (int) ($template->version ?? 1),
            ],
            'stages' => $template->stages->sortBy('sort_order')->values()->map(function (WorkflowStage $stage) {
                return [
                    'name' => (string) $stage->name,
                    'code' => (string) $stage->code,
                    'description' => $stage->description,
                    'stage_type' => $stage->resolvedStageType()?->value,
                    'ui_component' => $stage->ui_component,
                    'execution_mode' => $stage->resolvedExecutionMode()?->value,
                    'allow_skip' => (bool) $stage->allow_skip,
                    'requires_approval' => (bool) $stage->requires_approval,
                    'approval_role_id' => $stage->approval_role_id,
                    'questionnaire_template_id' => $stage->questionnaire_template_id,
                    'form_template_id' => $stage->form_template_id,
                    'component_key' => $stage->component_key,
                    'form_schema_json' => $stage->resolvedFormSchema(),
                    'risk_matrix_schema_json' => $stage->resolvedRiskMatrixSchema(),
                    'position_x' => $stage->position_x,
                    'position_y' => $stage->position_y,
                    'color' => $stage->color,
                    'icon' => $stage->icon,
                    'sort_order' => (int) $stage->sort_order,
                    'configuration_json' => $stage->resolvedConfiguration(),
                    'validation_rules_json' => $stage->resolvedValidationRules(),
                    'is_required' => (bool) $stage->is_required,
                    'is_repeatable' => (bool) $stage->is_repeatable,
                    'role_scope' => $stage->role_scope,
                ];
            })->all(),
            'transitions' => $template->transitions->values()->map(function (WorkflowTransition $transition) {
                return [
                    'from' => $transition->fromStage?->code,
                    'to' => $transition->toStage?->code,
                    'condition_type' => $transition->condition_type,
                    'condition_configuration' => $transition->condition_configuration ?? [],
                    'role_required' => $transition->role_required,
                    'is_automatic' => (bool) $transition->is_automatic,
                ];
            })->sortBy(fn (array $row) => implode(':', [$row['from'], $row['to'], $row['condition_type']]))->values()->all(),
        ];

        return sha1(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
    }

    private function lineageRootId(WorkflowTemplate $template): int
    {
        return (int) ($template->source_template_id ?: $template->id);
    }

    private function nextVersionNumber(int $rootId, ?int $ignoreTemplateId = null): int
    {
        return (int) WorkflowTemplate::query()
            ->where(function ($query) use ($rootId) {
                $query->whereKey($rootId)
                    ->orWhere('source_template_id', $rootId);
            })
            ->when($ignoreTemplateId !== null, fn ($query) => $query->whereKeyNot($ignoreTemplateId))
            ->max('version') + 1;
    }

    private function uniqueDraftSlug(WorkflowTemplate $template, int $rootId): string
    {
        $nextVersion = $this->nextVersionNumber($rootId);
        $base = Str::slug($template->name).'-v'.$nextVersion.'-draft';
        $slug = $base;

        while (WorkflowTemplate::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }
}
