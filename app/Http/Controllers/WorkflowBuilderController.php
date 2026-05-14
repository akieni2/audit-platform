<?php

namespace App\Http\Controllers;

use App\Domain\Workflow\Enums\WorkflowExecutionMode;
use App\Domain\Workflow\Enums\WorkflowStageType;
use App\Http\Requests\Workflows\StoreWorkflowStageRequest;
use App\Http\Requests\Workflows\StoreWorkflowTemplateRequest;
use App\Http\Requests\Workflows\StoreWorkflowTransitionRequest;
use App\Http\Requests\Workflows\UpdateWorkflowStageRequest;
use App\Http\Requests\Workflows\UpdateWorkflowTemplateRequest;
use App\Models\Department;
use App\Models\FormTemplate;
use App\Models\QuestionnaireTemplate;
use App\Models\Role;
use App\Models\WorkflowStage;
use App\Models\WorkflowTemplate;
use App\Models\WorkflowTransition;
use App\Services\Workflow\WorkflowPublishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

class WorkflowBuilderController extends Controller
{
    public function __construct(
        private WorkflowPublishingService $publishing,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', WorkflowTemplate::class);

        return view('workflows.builder.index', [
            'templates' => WorkflowTemplate::query()
                ->withCount(['stages', 'transitions', 'instances'])
                ->with(['department', 'sourceTemplate'])
                ->orderByRaw("CASE status
                    WHEN 'draft' THEN 0
                    WHEN 'published' THEN 1
                    WHEN 'deprecated' THEN 2
                    WHEN 'archived' THEN 3
                    ELSE 4 END")
                ->orderByDesc('updated_at')
                ->paginate(12)
                ->withQueryString(),
            'departmentOptions' => Department::query()->where('active', true)->orderBy('code')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->index();
    }

    public function edit(Request $request, WorkflowTemplate $template): View
    {
        $this->authorize('view', $template);

        $template->load([
            'department',
            'stages.formTemplate',
            'stages.questionnaireTemplate',
            'stages.approvalRole',
            'transitions.fromStage',
            'transitions.toStage',
            'sourceTemplate',
        ]);

        $selectedStageId = (int) $request->query('stage', $template->stages->sortBy('sort_order')->first()?->id);
        $selectedStage = $template->stages->firstWhere('id', $selectedStageId);

        return view('workflows.builder.edit', [
            'template' => $template,
            'selectedStage' => $selectedStage,
            'lineageTemplates' => $this->lineageTemplates($template),
            'departmentOptions' => Department::query()->where('active', true)->orderBy('code')->get(),
            'questionnaireTemplates' => QuestionnaireTemplate::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(),
            'formTemplates' => FormTemplate::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(),
            'roleOptions' => Role::query()->where('active', true)->orderByDesc('hierarchy_level')->orderBy('name')->get(),
            'stageTypeLabels' => WorkflowStageType::labels(),
            'executionModeLabels' => WorkflowExecutionMode::labels(),
        ]);
    }

    public function storeTemplate(StoreWorkflowTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', WorkflowTemplate::class);

        $template = DB::transaction(function () use ($request) {
            return WorkflowTemplate::query()->create([
                ...$request->validated(),
                'active' => false,
                'is_system' => false,
                'version' => 1,
                'status' => WorkflowTemplate::STATUS_DRAFT,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);
        });

        return redirect()
            ->route('workflow-builder.edit', $template)
            ->with('status', 'Brouillon de workflow créé.');
    }

    public function updateTemplate(UpdateWorkflowTemplateRequest $request, WorkflowTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        [$editableTemplate, $cloned] = $this->ensureEditableTemplate($template, $request->user());

        DB::transaction(function () use ($editableTemplate, $request) {
            $editableTemplate->update([
                ...$request->validated(),
                'updated_by' => $request->user()?->id,
            ]);
        });

        return redirect()
            ->route('workflow-builder.edit', $editableTemplate)
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis le workflow a été mis à jour.'
                : 'Workflow mis à jour.');
    }

    public function storeStage(StoreWorkflowStageRequest $request, WorkflowTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        [$editableTemplate, $cloned] = $this->ensureEditableTemplate($template, $request->user());
        $validated = $request->validated();

        $stage = DB::transaction(function () use ($editableTemplate, $validated) {
            return $editableTemplate->stages()->create([
                ...$validated,
                'sort_order' => $validated['sort_order'] ?? ((int) $editableTemplate->stages()->max('sort_order') + 1),
                'ui_component' => $validated['ui_component'] ?? 'stage-card',
                'configuration' => $validated['configuration_json'] ?? [],
                'configuration_json' => $validated['configuration_json'] ?? [],
            ]);
        });

        return redirect()
            ->route('workflow-builder.edit', ['template' => $editableTemplate, 'stage' => $stage->id])
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis l’étape a été ajoutée.'
                : 'Étape ajoutée au workflow.');
    }

    public function updateStage(UpdateWorkflowStageRequest $request, WorkflowStage $stage): RedirectResponse
    {
        $template = $stage->workflowTemplate;
        abort_unless($template instanceof WorkflowTemplate, 404);
        $this->authorize('update', $template);

        [$editableTemplate, $editableStage, $cloned] = $this->editableStageContext($stage, $request->user());
        $validated = $request->validated();

        DB::transaction(function () use ($editableStage, $validated) {
            $editableStage->update([
                ...$validated,
                'configuration' => $validated['configuration_json'] ?? [],
            ]);
        });

        return redirect()
            ->route('workflow-builder.edit', ['template' => $editableTemplate, 'stage' => $editableStage->id])
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis l’étape a été mise à jour.'
                : 'Étape mise à jour.');
    }

    public function destroyStage(Request $request, WorkflowStage $stage): RedirectResponse
    {
        $template = $stage->workflowTemplate;
        abort_unless($template instanceof WorkflowTemplate, 404);
        $this->authorize('update', $template);

        [$editableTemplate, $editableStage, $cloned] = $this->editableStageContext($stage, $request->user());

        DB::transaction(function () use ($editableStage) {
            $editableStage->incomingTransitions()->delete();
            $editableStage->outgoingTransitions()->delete();
            $editableStage->delete();
        });

        return redirect()
            ->route('workflow-builder.edit', $editableTemplate)
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis l’étape a été supprimée.'
                : 'Étape supprimée.');
    }

    public function storeTransition(StoreWorkflowTransitionRequest $request, WorkflowTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        [$editableTemplate, $cloned] = $this->ensureEditableTemplate($template, $request->user());
        $validated = $request->validated();

        $fromStageId = $this->mapStageIdForEditableTemplate($editableTemplate, $template, (int) $validated['from_stage_id']);
        $toStageId = $this->mapStageIdForEditableTemplate($editableTemplate, $template, (int) $validated['to_stage_id']);

        DB::transaction(function () use ($editableTemplate, $validated, $fromStageId, $toStageId) {
            $editableTemplate->transitions()->create([
                'from_stage_id' => $fromStageId,
                'to_stage_id' => $toStageId,
                'condition_type' => $validated['condition_type'] ?? null,
                'condition_configuration' => $validated['condition_configuration'] ?? null,
                'role_required' => $validated['role_required'] ?? null,
                'is_automatic' => (bool) ($validated['is_automatic'] ?? false),
            ]);
        });

        return redirect()
            ->route('workflow-builder.edit', $editableTemplate)
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis la transition a été ajoutée.'
                : 'Transition ajoutée.');
    }

    public function destroyTransition(Request $request, WorkflowTransition $transition): RedirectResponse
    {
        $template = $transition->workflowTemplate;
        abort_unless($template instanceof WorkflowTemplate, 404);
        $this->authorize('update', $template);

        [$editableTemplate, $editableTransition, $cloned] = $this->editableTransitionContext($transition, $request->user());

        DB::transaction(function () use ($editableTransition) {
            $editableTransition->delete();
        });

        return redirect()
            ->route('workflow-builder.edit', $editableTemplate)
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis la transition a été supprimée.'
                : 'Transition supprimée.');
    }

    public function publish(Request $request, WorkflowTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        try {
            $published = $this->publishing->publish($template, $request->user());
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('workflow-builder.edit', $template)
                ->withErrors(['publish' => $exception->getMessage()]);
        }

        return redirect()
            ->route('workflow-builder.edit', $published)
            ->with('status', 'Workflow publié et verrouillé pour édition directe.');
    }

    public function archive(Request $request, WorkflowTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $archived = DB::transaction(fn () => $this->publishing->archive($template, $request->user()));

        return redirect()
            ->route('workflow-builder.edit', $archived)
            ->with('status', 'Workflow archivé.');
    }

    /**
     * @return array{0:WorkflowTemplate,1:bool}
     */
    private function ensureEditableTemplate(WorkflowTemplate $template, ?\App\Models\User $actor = null): array
    {
        $editable = $this->publishing->ensureEditableDraft($template, $actor);

        return [$editable, $editable->id !== $template->id];
    }

    /**
     * @return array{0:WorkflowTemplate,1:WorkflowStage,2:bool}
     */
    private function editableStageContext(WorkflowStage $stage, ?\App\Models\User $actor = null): array
    {
        $template = $stage->workflowTemplate;
        abort_unless($template instanceof WorkflowTemplate, 404);

        [$editableTemplate, $cloned] = $this->ensureEditableTemplate($template, $actor);

        if (! $cloned) {
            return [$editableTemplate, $stage, false];
        }

        $editableStage = $editableTemplate->stages()->where('code', $stage->code)->orderBy('id')->first();
        abort_unless($editableStage instanceof WorkflowStage, 404);

        return [$editableTemplate, $editableStage, true];
    }

    /**
     * @return array{0:WorkflowTemplate,1:WorkflowTransition,2:bool}
     */
    private function editableTransitionContext(WorkflowTransition $transition, ?\App\Models\User $actor = null): array
    {
        $template = $transition->workflowTemplate;
        abort_unless($template instanceof WorkflowTemplate, 404);

        [$editableTemplate, $cloned] = $this->ensureEditableTemplate($template, $actor);

        if (! $cloned) {
            return [$editableTemplate, $transition, false];
        }

        $transition->loadMissing('fromStage', 'toStage');

        $fromStage = $transition->fromStage;
        $toStage = $transition->toStage;
        abort_unless($fromStage instanceof WorkflowStage && $toStage instanceof WorkflowStage, 404);

        $editableTransition = $editableTemplate->transitions()
            ->whereHas('fromStage', fn ($query) => $query->where('code', $fromStage->code))
            ->whereHas('toStage', fn ($query) => $query->where('code', $toStage->code))
            ->where('condition_type', $transition->condition_type)
            ->first();

        abort_unless($editableTransition instanceof WorkflowTransition, 404);

        return [$editableTemplate, $editableTransition, true];
    }

    private function mapStageIdForEditableTemplate(WorkflowTemplate $editableTemplate, WorkflowTemplate $sourceTemplate, int $stageId): int
    {
        if ($editableTemplate->is($sourceTemplate)) {
            return $stageId;
        }

        $sourceStage = $sourceTemplate->stages()->findOrFail($stageId);
        $editableStage = $editableTemplate->stages()->where('code', $sourceStage->code)->firstOrFail();

        return (int) $editableStage->id;
    }

    private function lineageTemplates(WorkflowTemplate $template)
    {
        $rootId = (int) ($template->source_template_id ?: $template->id);

        return WorkflowTemplate::query()
            ->where(function ($query) use ($rootId) {
                $query->whereKey($rootId)
                    ->orWhere('source_template_id', $rootId);
            })
            ->orderBy('version')
            ->get();
    }
}
