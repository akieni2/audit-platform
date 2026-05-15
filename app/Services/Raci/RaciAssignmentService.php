<?php

namespace App\Services\Raci;

use App\Models\Mission;
use App\Models\MissionRaciPreview;
use App\Models\RaciAssignment;
use App\Models\RaciMatrix;
use App\Models\RaciRole;
use App\Models\RaciTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RaciAssignmentService
{
    public function __construct(
        private RaciAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function assignForMission(RaciTemplate $template, Mission $mission, array $payload, ?User $actor = null): RaciMatrix
    {
        return DB::transaction(function () use ($template, $mission, $payload, $actor) {
            $matrix = RaciMatrix::query()->create([
                'raci_template_id' => $template->id,
                'mission_id' => $mission->id,
                'department_id' => $mission->department_id,
                'workflow_instance_id' => $payload['workflow_instance_id'] ?? null,
                'name' => $payload['name'] ?? $template->name,
                'process_label' => $payload['process_label'] ?? 'Matrice mission',
                'status' => $payload['status'] ?? 'draft',
                'metadata' => $payload['metadata'] ?? [],
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);

            foreach (($payload['assignments'] ?? []) as $index => $assignment) {
                $role = RaciRole::query()->find($assignment['raci_role_id'] ?? null);

                RaciAssignment::query()->create([
                    'raci_template_id' => $template->id,
                    'raci_matrix_id' => $matrix->id,
                    'raci_role_id' => $role?->id,
                    'mission_id' => $mission->id,
                    'department_id' => $mission->department_id,
                    'service_id' => $assignment['service_id'] ?? null,
                    'assigned_user_id' => $assignment['assigned_user_id'] ?? null,
                    'process_label' => $assignment['process_label'] ?? $matrix->process_label,
                    'process_sort_order' => (int) ($assignment['process_sort_order'] ?? $index),
                    'role_type' => $assignment['role_type'] ?? $role?->role_type?->value ?? 'responsible',
                    'responsibility_level' => $assignment['responsibility_level'] ?? $role?->responsibility_level?->value ?? 'moderate',
                    'status' => $assignment['status'] ?? 'draft',
                    'notes' => $assignment['notes'] ?? null,
                    'metadata' => $assignment['metadata'] ?? [],
                ]);
            }

            MissionRaciPreview::query()->create([
                'mission_id' => $mission->id,
                'status' => $matrix->status,
                'process_label' => $matrix->process_label,
                'metadata' => [
                    'raci_matrix_id' => $matrix->id,
                    'raci_template_id' => $template->id,
                    'assignments_count' => $matrix->assignments()->count(),
                ],
            ]);

            $firstAssignment = $matrix->assignments()->latest('id')->first();
            if ($firstAssignment instanceof RaciAssignment) {
                $this->audit->log(
                    eventName: 'raci.assignment.created',
                    assignment: $firstAssignment,
                    actor: $actor,
                    status: $matrix->status,
                    payload: [
                        'raci_matrix_id' => $matrix->id,
                        'mission_id' => $mission->id,
                    ],
                );
            }

            return $matrix->fresh(['assignments.raciRole']);
        });
    }
}
