<?php

namespace App\Services\Raci;

use App\Models\RaciTemplate;

class RaciMatrixBuilderService
{
    /**
     * @return array<string, mixed>
     */
    public function editorPayload(RaciTemplate $template): array
    {
        $template->loadMissing(['department', 'roles', 'assignments.raciRole']);

        $processes = $template->assignments
            ->groupBy('process_label')
            ->map(fn ($items, $label) => [
                'process_label' => $label ?: 'Processus',
                'assignments' => $items->sortBy('process_sort_order')->values(),
            ])
            ->values();

        return [
            'template' => $template,
            'roles' => $template->roles,
            'processes' => $processes,
            'matrix' => $this->matrix($template),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function matrix(RaciTemplate $template): array
    {
        $template->loadMissing(['roles', 'assignments.raciRole']);

        return $template->assignments
            ->groupBy('process_label')
            ->map(function ($assignments, $processLabel) use ($template) {
                $cells = $template->roles->map(function ($role) use ($assignments) {
                    $assignment = $assignments->firstWhere('raci_role_id', $role->id);

                    return [
                        'role' => $role,
                        'assignment' => $assignment,
                    ];
                });

                return [
                    'process_label' => $processLabel ?: 'Processus',
                    'cells' => $cells,
                ];
            })
            ->values()
            ->all();
    }
}
