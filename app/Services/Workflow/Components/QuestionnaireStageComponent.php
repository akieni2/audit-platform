<?php

namespace App\Services\Workflow\Components;

use App\Models\Entretien;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use App\Services\Workflow\Components\Contracts\WorkflowStageComponent;
use Illuminate\Http\Request;
use InvalidArgumentException;

class QuestionnaireStageComponent implements WorkflowStageComponent
{
    public function key(): string
    {
        return 'questionnaire_bridge';
    }

    public function aliases(): array
    {
        return ['questionnaire_bridge'];
    }

    public function buildViewData(WorkflowInstance $instance, WorkflowStage $stage, ?User $actor = null): array
    {
        $entretien = $this->resolveEntretien($instance, $stage);

        return [
            'view' => 'workflows.runtime.components.questionnaire-bridge',
            'stage' => $stage,
            'instance' => $instance,
            'entretien' => $entretien,
            'questionnaireUrl' => $entretien ? route('entretiens.conduite.show', $entretien) : null,
        ];
    }

    public function handleSubmission(Request $request, WorkflowInstance $instance, WorkflowStage $stage, ?User $actor = null): array
    {
        throw new InvalidArgumentException('Ce stage doit être complété via le runtime questionnaire existant.');
    }

    private function resolveEntretien(WorkflowInstance $instance, WorkflowStage $stage): ?Entretien
    {
        return Entretien::query()
            ->where('mission_id', $instance->mission_id)
            ->when(
                $stage->questionnaire_template_id !== null,
                fn ($query) => $query->where('questionnaire_template_id', $stage->questionnaire_template_id)
            )
            ->latest('id')
            ->first();
    }
}
