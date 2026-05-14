<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\WorkflowStage;
use App\Services\Workflow\Components\WorkflowStageComponentRegistry;
use App\Services\Workflow\WorkflowCompatibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class WorkflowStageRuntimeController extends Controller
{
    public function __construct(
        private WorkflowCompatibilityService $compatibility,
        private WorkflowStageComponentRegistry $components,
    ) {}

    public function showCurrent(Request $request, Mission $mission): View|RedirectResponse
    {
        $this->authorize('view', $mission);

        $instance = $this->compatibility->ensureMissionWorkflow($mission, $request->user());
        $instance->loadMissing(['currentStage', 'workflowTemplate', 'mission']);

        if (! $instance->currentStage instanceof WorkflowStage) {
            return redirect()
                ->route('missions.show', $mission)
                ->with('status', 'Aucune étape active à exécuter.');
        }

        return $this->showStage($request, $mission, $instance->currentStage);
    }

    public function showStage(Request $request, Mission $mission, WorkflowStage $stage): View
    {
        $this->authorize('view', $mission);

        $instance = $this->compatibility->ensureMissionWorkflow($mission, $request->user());
        $instance->loadMissing(['currentStage', 'workflowTemplate', 'mission']);

        abort_unless(
            $instance->currentStage instanceof WorkflowStage
            && (int) $instance->currentStage->id === (int) $stage->id,
            404
        );

        $component = $this->components->resolve($stage);
        $payload = $component->buildViewData($instance, $stage, $request->user());

        return view($payload['view'], $payload);
    }

    public function submitStage(Request $request, Mission $mission, WorkflowStage $stage): RedirectResponse
    {
        $this->authorize('view', $mission);

        $instance = $this->compatibility->ensureMissionWorkflow($mission, $request->user());
        $instance->loadMissing(['currentStage', 'workflowTemplate', 'mission']);

        abort_unless(
            $instance->currentStage instanceof WorkflowStage
            && (int) $instance->currentStage->id === (int) $stage->id,
            404
        );

        try {
            $component = $this->components->resolve($stage);
            $result = $component->handleSubmission($request, $instance, $stage, $request->user());
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('workflow-runtime.stage', ['mission' => $mission, 'stage' => $stage])
                ->withErrors(['runtime' => $exception->getMessage()]);
        }

        $freshInstance = $result['instance'] ?? null;
        if ($freshInstance?->currentStage instanceof WorkflowStage && (int) $freshInstance->currentStage->id !== (int) $stage->id) {
            return redirect()
                ->route('workflow-runtime.stage', ['mission' => $mission, 'stage' => $freshInstance->currentStage])
                ->with('status', $result['message'] ?? 'Étape enregistrée.');
        }

        return redirect()
            ->route('workflow-runtime.stage', ['mission' => $mission, 'stage' => $stage])
            ->with('status', $result['message'] ?? 'Étape enregistrée.');
    }
}
