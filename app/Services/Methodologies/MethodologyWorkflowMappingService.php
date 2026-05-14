<?php

namespace App\Services\Methodologies;

use App\Models\MethodologyMapping;
use App\Models\MethodologyTemplate;
use App\Models\User;
use Illuminate\Support\Collection;

class MethodologyWorkflowMappingService
{
    /**
     * @return array<string, mixed>
     */
    public function resolveStack(MethodologyTemplate $template, ?int $departmentId = null): array
    {
        $template->loadMissing([
            'categories',
            'controls',
            'requirements',
            'mappings.workflowTemplate',
            'mappings.workflowStage',
            'mappings.formTemplate',
            'mappings.questionnaireTemplate',
            'mappings.controlLibrary',
            'mappings.controlMeasure',
            'mappings.taxonomyTerm.taxonomy',
        ]);

        $mappings = $template->mappings
            ->when($departmentId !== null, function (Collection $items) use ($departmentId) {
                return $items->filter(fn (MethodologyMapping $mapping) => $mapping->department_id === null || (int) $mapping->department_id === (int) $departmentId);
            })
            ->values();

        return [
            'template' => $template,
            'categories_count' => $template->categories->count(),
            'controls_count' => $template->controls->count(),
            'requirements_count' => $template->requirements->count(),
            'mappings_count' => $mappings->count(),
            'workflows' => $mappings->pluck('workflowTemplate')->filter()->unique('id')->values(),
            'stages' => $mappings->pluck('workflowStage')->filter()->unique('id')->values(),
            'forms' => $mappings->pluck('formTemplate')->filter()->unique('id')->values(),
            'questionnaires' => $mappings->pluck('questionnaireTemplate')->filter()->unique('id')->values(),
            'control_libraries' => $mappings->pluck('controlLibrary')->filter()->unique('id')->values(),
            'control_measures' => $mappings->pluck('controlMeasure')->filter()->unique('id')->values(),
            'taxonomy_terms' => $mappings->pluck('taxonomyTerm')->filter()->unique('id')->values(),
            'risk_categories' => $mappings->pluck('risk_category')->filter()->unique()->values(),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createMapping(MethodologyTemplate $template, array $attributes, ?User $actor = null): MethodologyMapping
    {
        return MethodologyMapping::query()->create([
            'methodology_template_id' => $template->id,
            'mapping_type' => $attributes['mapping_type'] ?? 'reference',
            'methodology_control_id' => $attributes['methodology_control_id'] ?? null,
            'methodology_requirement_id' => $attributes['methodology_requirement_id'] ?? null,
            'workflow_template_id' => $attributes['workflow_template_id'] ?? null,
            'workflow_stage_id' => $attributes['workflow_stage_id'] ?? null,
            'form_template_id' => $attributes['form_template_id'] ?? null,
            'questionnaire_template_id' => $attributes['questionnaire_template_id'] ?? null,
            'control_library_id' => $attributes['control_library_id'] ?? null,
            'control_measure_id' => $attributes['control_measure_id'] ?? null,
            'taxonomy_term_id' => $attributes['taxonomy_term_id'] ?? null,
            'department_id' => $attributes['department_id'] ?? null,
            'risk_category' => $attributes['risk_category'] ?? null,
            'mapping_payload' => $attributes['mapping_payload'] ?? [],
            'created_by' => $actor?->id,
        ]);
    }

    /**
     * @return array<string, int>
     */
    public function coverage(MethodologyTemplate $template): array
    {
        $template->loadMissing(['categories', 'controls', 'requirements', 'mappings']);

        return [
            'categories' => $template->categories->count(),
            'controls' => $template->controls->count(),
            'requirements' => $template->requirements->count(),
            'mappings' => $template->mappings->count(),
        ];
    }
}
