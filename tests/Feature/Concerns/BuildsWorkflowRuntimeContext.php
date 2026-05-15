<?php

namespace Tests\Feature\Concerns;

use App\Models\Department;
use App\Models\Mission;
use App\Models\Role;
use App\Models\RaciRole;
use App\Models\RaciTemplate;
use App\Models\SwotCategory;
use App\Models\SwotEntry;
use App\Models\SwotTemplate;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Models\WorkflowTemplate;
use Carbon\Carbon;

trait BuildsWorkflowRuntimeContext
{
    private function createDepartment(string $code = 'WFRT'): Department
    {
        return Department::query()->create([
            'name' => 'Pôle '.$code,
            'code' => $code,
            'type' => 'pole',
            'description' => 'Workflow runtime tests',
            'active' => true,
        ]);
    }

    private function createRole(string $slug = 'inspecteur_services', int $level = 100): Role
    {
        return Role::query()->create([
            'slug' => $slug,
            'name' => $slug,
            'hierarchy_level' => $level,
            'active' => true,
        ]);
    }

    private function createUser(string $slug = 'inspecteur_services', ?Department $department = null, int $level = 100): User
    {
        $role = $this->createRole($slug, $level);

        return User::factory()->create([
            'department_id' => $department?->id,
            'role_id' => $role->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
    }

    private function createMission(User $auditeur, Department $department, string $status = Mission::STATUS_EN_COURS): Mission
    {
        return Mission::query()->create([
            'organisation' => 'Mission '.$department->code,
            'description' => 'Mission runtime test',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $auditeur->id,
            'department_id' => $department->id,
            'mission_status' => $status,
        ]);
    }

    private function createWorkflowTemplate(Department $department, string $suffix = 'runtime'): WorkflowTemplate
    {
        return WorkflowTemplate::query()->create([
            'department_id' => $department->id,
            'name' => 'Workflow '.$suffix,
            'slug' => 'workflow-'.$suffix.'-'.strtolower($department->code),
            'code' => 'WF_'.strtoupper($department->code).'_'.strtoupper(substr($suffix, 0, 4)),
            'active' => true,
            'version' => 1,
            'status' => WorkflowTemplate::STATUS_PUBLISHED,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createStage(WorkflowTemplate $workflow, array $overrides = []): WorkflowStage
    {
        $defaults = [
            'workflow_template_id' => $workflow->id,
            'name' => 'Stage '.((int) $workflow->stages()->count() + 1),
            'code' => 'STAGE_'.((int) $workflow->stages()->count() + 1),
            'stage_type' => 'custom',
            'execution_mode' => 'manual',
            'component_key' => 'system_stage',
            'sort_order' => (int) $workflow->stages()->count(),
            'ui_component' => 'stage-card',
            'configuration' => [],
            'configuration_json' => [],
            'position_x' => (int) $workflow->stages()->count() * 240,
            'position_y' => 0,
            'color' => '#0A2A66',
            'icon' => 'workflow',
            'is_required' => true,
        ];

        return WorkflowStage::query()->create(array_replace($defaults, $overrides));
    }

    private function createSwotTemplate(Department $department): SwotTemplate
    {
        $template = SwotTemplate::query()->create([
            'department_id' => $department->id,
            'name' => 'SWOT '.$department->code,
            'slug' => 'swot-'.strtolower($department->code),
            'code' => 'SWOT_'.$department->code,
            'analysis_scope' => 'mission',
            'active' => true,
            'version' => 1,
            'lifecycle_status' => SwotTemplate::STATUS_PUBLISHED,
        ]);

        $category = SwotCategory::query()->create([
            'swot_template_id' => $template->id,
            'name' => 'Forces',
            'category_type' => 'strength',
            'weight' => 1,
            'sort_order' => 0,
        ]);

        SwotEntry::query()->create([
            'swot_template_id' => $template->id,
            'swot_category_id' => $category->id,
            'department_id' => $department->id,
            'title' => 'Maturite terrain',
            'impact_level' => 'high',
            'priority_level' => 'high',
            'weight' => 1.2,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return $template;
    }

    private function createRaciTemplate(Department $department): RaciTemplate
    {
        $template = RaciTemplate::query()->create([
            'department_id' => $department->id,
            'name' => 'RACI '.$department->code,
            'slug' => 'raci-'.strtolower($department->code),
            'code' => 'RACI_'.$department->code,
            'analysis_scope' => 'mission',
            'active' => true,
            'version' => 1,
            'lifecycle_status' => RaciTemplate::STATUS_PUBLISHED,
        ]);

        RaciRole::query()->create([
            'raci_template_id' => $template->id,
            'department_id' => $department->id,
            'name' => 'Chef mission',
            'code' => 'CHEF',
            'role_type' => 'accountable',
            'responsibility_level' => 'high',
            'sort_order' => 0,
        ]);

        return $template;
    }
}
