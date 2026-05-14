<?php

namespace App\Services\Forms;

use Illuminate\Support\Collection;

class FormWizardService
{
    /**
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    public function build(array $form): array
    {
        $fields = collect($form['visible_fields'] ?? [])->values();

        if ($fields->isEmpty()) {
            return [
                'steps' => [],
                'fullscreen' => false,
                'collapsible_sections' => false,
            ];
        }

        $grouped = $fields->groupBy(function (array $field, int $index) {
            $configuration = $field['configuration'] ?? [];

            return $configuration['wizard_step']
                ?? $configuration['section']
                ?? ('Étape '.(((int) floor($index / 5)) + 1));
        });

        return [
            'steps' => $grouped->map(fn (Collection $stepFields, string|int $groupLabel) => [
                'group_label' => is_string($groupLabel) ? $groupLabel : null,
                'fields' => $stepFields->values()->all(),
            ])->values()->map(function (array $step, int $index) {
                $first = $step['fields'][0] ?? [];
                $groupLabel = $step['group_label'] ?? null;

                return [
                    'id' => 'step-'.($index + 1),
                    'index' => $index,
                    'label' => $groupLabel ?: ($first['configuration']['wizard_step'] ?? 'Étape '.($index + 1)),
                    'title' => $first['configuration']['step_title'] ?? ($groupLabel ?: 'Étape '.($index + 1)),
                    'field_keys' => collect($step['fields'])->pluck('field_key')->all(),
                    'fields' => $step['fields'],
                ];
            })->all(),
            'fullscreen' => (bool) data_get($form, 'snapshot.template.configuration.fullscreen', false),
            'collapsible_sections' => true,
        ];
    }
}
