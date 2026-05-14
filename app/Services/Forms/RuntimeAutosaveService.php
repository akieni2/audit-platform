<?php

namespace App\Services\Forms;

use App\Models\FormSubmission;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;

class RuntimeAutosaveService
{
    /**
     * @return array<string, mixed>
     */
    public function build(?FormSubmission $submission, WorkflowInstance $instance, WorkflowStage $stage): array
    {
        $lastSavedAt = $submission?->submitted_at;

        return [
            'enabled' => true,
            'status' => $submission?->status ?? 'draft',
            'label' => $lastSavedAt ? 'Brouillon sauvegardé' : 'Autosave prêt',
            'last_saved_at' => $lastSavedAt?->format('d/m/Y H:i'),
            'interval_seconds' => 20,
            'submission_id' => $submission?->id,
            'stage_id' => $stage->id,
            'instance_id' => $instance->id,
        ];
    }
}
