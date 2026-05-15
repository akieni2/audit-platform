<?php

namespace Tests\Feature\Concerns;

use App\Models\ControlLibrary;
use App\Models\ControlMeasure;
use App\Models\Department;
use App\Models\MethodologyCategory;
use App\Models\MethodologyControl;
use App\Models\MethodologyRequirement;
use App\Models\MethodologyTemplate;
use App\Models\Mission;
use App\Models\Role;
use App\Models\RaciRole;
use App\Models\RaciTemplate;
use App\Models\SwotCategory;
use App\Models\SwotEntry;
use App\Models\SwotTemplate;
use App\Models\Taxonomy;
use App\Models\TaxonomyTerm;
use App\Models\User;
use App\Models\WorkflowTemplate;
use Carbon\Carbon;

trait BuildsEnterpriseGovernanceContext
{
    private function governanceDepartment(string $code = 'GOV'): Department
    {
        return Department::query()->create([
            'name' => 'Département '.$code,
            'code' => $code,
            'type' => 'pole',
            'description' => 'Test Sprint 9',
            'active' => true,
        ]);
    }

    private function governanceRole(string $slug, int $level = 100): Role
    {
        return Role::query()->create([
            'slug' => $slug,
            'name' => $slug,
            'hierarchy_level' => $level,
            'active' => true,
        ]);
    }

    private function governanceUser(Department $department, string $slug = 'inspecteur_services', int $level = 100): User
    {
        return User::factory()->create([
            'department_id' => $department->id,
            'role_id' => $this->governanceRole($slug, $level)->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
    }

    private function governanceMission(Department $department, User $user, string $status = Mission::STATUS_EN_COURS): Mission
    {
        return Mission::query()->create([
            'organisation' => 'Mission '.$department->code,
            'description' => 'Contexte enterprise',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $user->id,
            'department_id' => $department->id,
            'mission_status' => $status,
        ]);
    }

    private function governanceWorkflow(Department $department, array $attributes = []): WorkflowTemplate
    {
        return WorkflowTemplate::query()->create(array_replace([
            'department_id' => $department->id,
            'name' => 'Workflow '.$department->code,
            'slug' => 'workflow-'.$department->code.'-'.uniqid(),
            'code' => 'WF_'.$department->code,
            'active' => true,
            'version' => 1,
            'status' => WorkflowTemplate::STATUS_PUBLISHED,
        ], $attributes));
    }

    private function governanceMethodology(Department $department, array $attributes = []): MethodologyTemplate
    {
        return MethodologyTemplate::query()->create(array_replace([
            'department_id' => $department->id,
            'name' => 'ISO 27001 '.$department->code,
            'slug' => 'iso-27001-'.$department->code.'-'.uniqid(),
            'framework_key' => 'ISO27001',
            'code' => 'ISO27001-'.$department->code,
            'active' => true,
            'is_global' => false,
            'version' => 1,
            'lifecycle_status' => MethodologyTemplate::STATUS_PUBLISHED,
        ], $attributes));
    }

    private function governanceMethodologyCategory(MethodologyTemplate $template, array $attributes = []): MethodologyCategory
    {
        return MethodologyCategory::query()->create(array_replace([
            'methodology_template_id' => $template->id,
            'name' => 'Catégorie de test',
            'code' => 'CAT-1',
            'sort_order' => 0,
        ], $attributes));
    }

    private function governanceMethodologyControl(MethodologyTemplate $template, MethodologyCategory $category, array $attributes = []): MethodologyControl
    {
        return MethodologyControl::query()->create(array_replace([
            'methodology_template_id' => $template->id,
            'methodology_category_id' => $category->id,
            'control_reference' => 'A.5.1',
            'title' => 'Contrôle de test',
            'criticality' => 'high',
        ], $attributes));
    }

    private function governanceMethodologyRequirement(MethodologyTemplate $template, MethodologyCategory $category, MethodologyControl $control, array $attributes = []): MethodologyRequirement
    {
        return MethodologyRequirement::query()->create(array_replace([
            'methodology_template_id' => $template->id,
            'methodology_category_id' => $category->id,
            'methodology_control_id' => $control->id,
            'requirement_reference' => 'REQ-1',
            'title' => 'Exigence de test',
            'status' => 'active',
        ], $attributes));
    }

    private function governanceTaxonomy(Department $department, array $attributes = []): Taxonomy
    {
        return Taxonomy::query()->create(array_replace([
            'department_id' => $department->id,
            'name' => 'Taxonomie risques '.$department->code,
            'slug' => 'taxonomy-'.$department->code.'-'.uniqid(),
            'taxonomy_type' => 'risk',
            'active' => true,
        ], $attributes));
    }

    private function governanceTaxonomyTerm(Taxonomy $taxonomy, array $attributes = []): TaxonomyTerm
    {
        return TaxonomyTerm::query()->create(array_replace([
            'taxonomy_id' => $taxonomy->id,
            'name' => 'Cybersécurité',
            'code' => 'CYBER',
            'alias_terms' => ['sécurité si', 'cyber'],
            'sort_order' => 0,
        ], $attributes));
    }

    private function governanceControlLibrary(Department $department, MethodologyTemplate $methodology, array $attributes = []): ControlLibrary
    {
        return ControlLibrary::query()->create(array_replace([
            'department_id' => $department->id,
            'methodology_template_id' => $methodology->id,
            'name' => 'Bibliothèque '.$department->code,
            'slug' => 'control-library-'.$department->code.'-'.uniqid(),
            'visibility_scope' => 'department',
            'active' => true,
        ], $attributes));
    }

    private function governanceControlMeasure(ControlLibrary $library, MethodologyControl $control, TaxonomyTerm $term, Department $department, array $attributes = []): ControlMeasure
    {
        return ControlMeasure::query()->create(array_replace([
            'control_library_id' => $library->id,
            'methodology_control_id' => $control->id,
            'taxonomy_term_id' => $term->id,
            'department_id' => $department->id,
            'code' => 'CTRL-CYBER',
            'title' => 'Revue périodique des accès',
            'execution_frequency' => 'monthly',
            'maturity_level' => 3,
        ], $attributes));
    }

    private function governanceSwotTemplate(Department $department, array $attributes = []): SwotTemplate
    {
        return SwotTemplate::query()->create(array_replace([
            'department_id' => $department->id,
            'name' => 'SWOT '.$department->code,
            'slug' => 'swot-'.$department->code.'-'.uniqid(),
            'code' => 'SWOT-'.$department->code,
            'analysis_scope' => 'mission',
            'active' => true,
            'is_global' => false,
            'version' => 1,
            'lifecycle_status' => SwotTemplate::STATUS_PUBLISHED,
        ], $attributes));
    }

    private function governanceSwotCategory(SwotTemplate $template, array $attributes = []): SwotCategory
    {
        return SwotCategory::query()->create(array_replace([
            'swot_template_id' => $template->id,
            'name' => 'Forces',
            'code' => 'STR',
            'category_type' => 'strength',
            'weight' => 1,
            'sort_order' => 0,
        ], $attributes));
    }

    private function governanceSwotEntry(SwotTemplate $template, SwotCategory $category, Department $department, array $attributes = []): SwotEntry
    {
        return SwotEntry::query()->create(array_replace([
            'swot_template_id' => $template->id,
            'swot_category_id' => $category->id,
            'department_id' => $department->id,
            'title' => 'Capacite d execution',
            'impact_level' => 'high',
            'priority_level' => 'high',
            'weight' => 1.5,
            'is_active' => true,
            'sort_order' => 0,
        ], $attributes));
    }

    private function governanceRaciTemplate(Department $department, array $attributes = []): RaciTemplate
    {
        return RaciTemplate::query()->create(array_replace([
            'department_id' => $department->id,
            'name' => 'RACI '.$department->code,
            'slug' => 'raci-'.$department->code.'-'.uniqid(),
            'code' => 'RACI-'.$department->code,
            'analysis_scope' => 'mission',
            'active' => true,
            'is_global' => false,
            'version' => 1,
            'lifecycle_status' => RaciTemplate::STATUS_PUBLISHED,
        ], $attributes));
    }

    private function governanceRaciRole(RaciTemplate $template, Department $department, array $attributes = []): RaciRole
    {
        return RaciRole::query()->create(array_replace([
            'raci_template_id' => $template->id,
            'department_id' => $department->id,
            'name' => 'Responsable audit',
            'code' => 'RESP',
            'role_type' => 'responsible',
            'responsibility_level' => 'high',
            'sort_order' => 0,
        ], $attributes));
    }
}
