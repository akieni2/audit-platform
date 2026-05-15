<?php

namespace App\Services\Raci;

use App\Models\RaciAssignment;
use App\Models\RaciValidation;
use App\Models\User;

class RaciValidationService
{
    public function __construct(
        private RaciAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(RaciAssignment $assignment, array $payload, ?User $actor = null): RaciValidation
    {
        $validation = RaciValidation::query()->create([
            'raci_template_id' => $assignment->raci_template_id,
            'raci_matrix_id' => $assignment->raci_matrix_id,
            'raci_assignment_id' => $assignment->id,
            'mission_id' => $assignment->mission_id,
            'validator_user_id' => $actor?->id,
            'status' => $payload['status'] ?? 'pending',
            'notes' => $payload['notes'] ?? null,
            'metadata' => $payload['metadata'] ?? [],
            'validated_at' => now(),
        ]);

        $this->audit->log(
            eventName: 'raci.validation.recorded',
            assignment: $assignment,
            actor: $actor,
            status: $validation->status?->value ?? $validation->getRawOriginal('status'),
            payload: [
                'raci_validation_id' => $validation->id,
            ],
        );

        return $validation;
    }
}
