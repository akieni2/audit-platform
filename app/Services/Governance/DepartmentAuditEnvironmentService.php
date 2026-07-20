<?php

namespace App\Services\Governance;

use App\Models\ControlLibrary;
use App\Models\Department;
use App\Models\MethodologyTemplate;
use App\Models\QuestionnaireTemplate;
use App\Models\RaciTemplate;
use App\Models\SwotTemplate;
use App\Models\User;
use App\Models\WorkflowTemplate;
use App\Services\Tenant\TenantResolutionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** Provisionne l'espace d'audit isolé d'une structure à partir de son référentiel. */
class DepartmentAuditEnvironmentService
{
    public function __construct(private readonly TenantResolutionService $tenants)
    {
    }

    public function provision(Department $department, MethodologyTemplate $methodology, ?User $actor = null): void
    {
        if (! $methodology->active) {
            throw new RuntimeException('Le référentiel d’audit sélectionné est inactif.');
        }

        DB::transaction(function () use ($department, $methodology, $actor): void {
            $tenant = $this->tenants->ensureTenantForDepartment((int) $department->id);
            $key = Str::slug($department->code ?: 'structure-'.$department->id);
            $ownerId = $actor?->id;

            $workflow = WorkflowTemplate::query()->firstOrCreate(
                ['department_id' => $department->id, 'slug' => $key.'-workflow-audit', 'version' => 1],
                [
                    'methodology_template_id' => $methodology->id,
                    'name' => 'Workflow d’audit — '.$department->name,
                    'description' => 'Workflow personnalisable fondé sur '.$methodology->name.'.',
                    'code' => strtoupper($department->code).'-WF',
                    'active' => true,
                    'is_system' => false,
                    'status' => WorkflowTemplate::STATUS_DRAFT,
                    'visibility_scope' => 'department',
                    'sharing_mode' => 'private',
                    'is_global_template' => false,
                    'is_private_template' => true,
                    'governance_tags' => ['methodology_id' => $methodology->id, 'provisioned' => true],
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]
            );
            $workflow->update([
                'methodology_template_id' => $methodology->id,
                'governance_tags' => array_replace($workflow->governance_tags ?? [], ['methodology_id' => $methodology->id]),
            ]);

            $questionnaire = QuestionnaireTemplate::query()->firstOrCreate(
                ['slug' => $key.'-bibliotheque-questions'],
                [
                    'name' => 'Bibliothèque de questions — '.$department->name,
                    'description' => 'Questions et questionnaires adaptés au référentiel '.$methodology->name.'.',
                    'methodology_template_id' => $methodology->id,
                    'department_scope' => [$department->id],
                    'visibility_scope' => 'department',
                    'sharing_mode' => 'private',
                    'is_global_template' => false,
                    'is_private_template' => true,
                    'governance_tags' => ['methodology_id' => $methodology->id, 'provisioned' => true],
                    'active' => true,
                    'version' => 1,
                    'lifecycle_status' => QuestionnaireTemplate::STATUS_DRAFT,
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]
            );
            $questionnaire->update([
                'methodology_template_id' => $methodology->id,
                'department_scope' => [$department->id],
                'governance_tags' => array_replace($questionnaire->governance_tags ?? [], ['methodology_id' => $methodology->id]),
            ]);

            $controls = ControlLibrary::query()->firstOrCreate(
                ['slug' => $key.'-bibliotheque-controles'],
                [
                    'department_id' => $department->id,
                    'methodology_template_id' => $methodology->id,
                    'name' => 'Bibliothèque de contrôles — '.$department->name,
                    'description' => 'Contrôles propres à la structure et au référentiel choisi.',
                    'visibility_scope' => 'department',
                    'active' => true,
                    'metadata' => ['provisioned' => true],
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]
            );
            $controls->update(['methodology_template_id' => $methodology->id]);

            $raci = RaciTemplate::query()->firstOrCreate(
                ['slug' => $key.'-raci', 'version' => 1],
                [
                    'department_id' => $department->id,
                    'name' => 'RACI — '.$department->name,
                    'code' => strtoupper($department->code).'-RACI',
                    'description' => 'Matrice des responsabilités de la structure.',
                    'analysis_scope' => 'department',
                    'active' => true,
                    'is_global' => false,
                    'lifecycle_status' => RaciTemplate::STATUS_DRAFT,
                    'metadata' => ['methodology_id' => $methodology->id, 'provisioned' => true],
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]
            );

            $swot = SwotTemplate::query()->firstOrCreate(
                ['slug' => $key.'-swot', 'version' => 1],
                [
                    'department_id' => $department->id,
                    'name' => 'SWOT — '.$department->name,
                    'code' => strtoupper($department->code).'-SWOT',
                    'description' => 'Analyse stratégique de la structure.',
                    'analysis_scope' => 'department',
                    'active' => true,
                    'is_global' => false,
                    'lifecycle_status' => SwotTemplate::STATUS_DRAFT,
                    'metadata' => ['methodology_id' => $methodology->id, 'provisioned' => true],
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]
            );

            $department->update([
                'intelligence_profile' => array_replace_recursive($department->intelligence_profile ?? [], [
                    'audit_environment' => [
                        'status' => 'ready',
                        'methodology_template_id' => $methodology->id,
                        'tenant_context_id' => $tenant?->id,
                        'workflow_template_id' => $workflow->id,
                        'raci_template_id' => $raci->id,
                        'swot_template_id' => $swot->id,
                        'modules' => ['workflows', 'questions', 'questionnaires', 'risks', 'controls', 'raci', 'swot'],
                        'provisioned_at' => now()->toIso8601String(),
                    ],
                ]),
            ]);
        });
    }
}
