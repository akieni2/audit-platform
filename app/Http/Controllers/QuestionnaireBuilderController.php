<?php

namespace App\Http\Controllers;

use App\Http\Requests\Questionnaires\StoreQuestionnaireQuestionRequest;
use App\Http\Requests\Questionnaires\StoreQuestionnaireSectionRequest;
use App\Http\Requests\Questionnaires\StoreQuestionnaireTemplateRequest;
use App\Http\Requests\Questionnaires\UpdateQuestionnaireQuestionRequest;
use App\Http\Requests\Questionnaires\UpdateQuestionnaireSectionRequest;
use App\Http\Requests\Questionnaires\UpdateQuestionnaireTemplateRequest;
use App\Models\Department;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use App\Models\QuestionnaireTemplate;
use App\Services\Iam\SecurityAuditService;
use App\Services\Questionnaires\QuestionnairePublishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

class QuestionnaireBuilderController extends Controller
{
    public function __construct(
        private QuestionnairePublishingService $publishing,
        private SecurityAuditService $audit,
    ) {}

    public function index(): View
    {
        $templates = QuestionnaireTemplate::query()
            ->whereNull('mission_id')
            ->withCount(['sections', 'entretiens'])
            ->with(['sections.questions', 'sourceTemplate'])
            ->orderByRaw("CASE lifecycle_status
                WHEN 'draft' THEN 0
                WHEN 'published' THEN 1
                WHEN 'deprecated' THEN 2
                WHEN 'archived' THEN 3
                ELSE 4 END")
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('questionnaires.builder.index', [
            'templates' => $templates,
            'departmentOptions' => Department::query()->where('active', true)->orderBy('code')->get(),
        ]);
    }

    public function edit(QuestionnaireTemplate $template): View
    {
        $this->authorize('view', $template);

        $template->load([
            'sections.questions' => fn ($query) => $query->orderBy('sort_order'),
            'sections.parent',
            'sourceTemplate',
            'mission',
        ]);

        return view('questionnaires.builder.edit', [
            'template' => $template,
            'lineageTemplates' => $this->lineageTemplates($template),
            'questionTypeLabels' => QuestionnaireQuestion::questionTypeLabels(),
            'criticalityOptions' => \App\Domain\Risk\Enums\CriticalityLevel::options(),
            'departmentOptions' => Department::query()->where('active', true)->orderBy('code')->get(),
        ]);
    }

    public function storeTemplate(StoreQuestionnaireTemplateRequest $request): RedirectResponse
    {
        $template = DB::transaction(function () use ($request) {
            return QuestionnaireTemplate::query()->create([
                ...$request->validated(),
                'active' => false,
                'version' => 1,
                'lifecycle_status' => QuestionnaireTemplate::STATUS_DRAFT,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);
        });

        $this->audit->questionnaireTemplateCreated($request->user(), $template, $request);

        return redirect()
            ->route('questionnaire-builder.edit', $template)
            ->with('status', 'Brouillon de questionnaire créé.');
    }

    public function updateTemplate(UpdateQuestionnaireTemplateRequest $request, QuestionnaireTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        [$editableTemplate, $cloned] = $this->ensureEditableTemplate($template, $request->user());

        DB::transaction(function () use ($editableTemplate, $request) {
            $editableTemplate->update([
                ...$request->validated(),
                'active' => $editableTemplate->lifecycle_status === QuestionnaireTemplate::STATUS_PUBLISHED,
                'updated_by' => $request->user()?->id,
            ]);
        });

        $this->audit->questionnaireTemplateUpdated($request->user(), $editableTemplate->fresh(), $request);

        return redirect()
            ->route('questionnaire-builder.edit', $editableTemplate)
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis mise à jour.'
                : 'Template mis à jour.');
    }

    public function storeSection(StoreQuestionnaireSectionRequest $request, QuestionnaireTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        [$editableTemplate, $cloned] = $this->ensureEditableTemplate($template, $request->user());

        DB::transaction(function () use ($editableTemplate, $request) {
            $validated = $request->validated();
            $validated['parent_section_id'] = $this->validatedParentId($editableTemplate, $validated);
            $editableTemplate->sections()->create([
                ...$validated,
                'sort_order' => $validated['sort_order'] ?? ($editableTemplate->sections()->max('sort_order') + 1),
            ]);
        });

        return redirect()
            ->route('questionnaire-builder.edit', $editableTemplate)
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis la section a été ajoutée.'
                : 'Section ajoutée.');
    }

    public function updateSection(UpdateQuestionnaireSectionRequest $request, QuestionnaireSection $section): RedirectResponse
    {
        $template = $section->template;
        abort_unless($template instanceof QuestionnaireTemplate, 404);
        $this->authorize('update', $template);

        [$editableTemplate, $editableSection, $cloned] = $this->editableSectionContext($section, $request->user());

        DB::transaction(function () use ($editableTemplate, $editableSection, $request) {
            $validated = $request->validated();
            $validated['parent_section_id'] = $this->validatedParentId($editableTemplate, $validated, $editableSection);
            $editableSection->update($validated);
        });

        return redirect()
            ->route('questionnaire-builder.edit', $editableTemplate)
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis la section a été mise à jour.'
                : 'Section mise à jour.');
    }

    public function destroySection(Request $request, QuestionnaireSection $section): RedirectResponse
    {
        $template = $section->template;
        abort_unless($template instanceof QuestionnaireTemplate, 404);
        $this->authorize('update', $template);

        [$editableTemplate, $editableSection, $cloned] = $this->editableSectionContext($section, $request->user());

        if ($editableSection->children()->exists()) {
            return redirect()
                ->route('questionnaire-builder.edit', $editableTemplate)
                ->withErrors(['section' => 'Déplacez ou archivez d’abord les éléments enfants de cette structure.']);
        }

        DB::transaction(function () use ($editableSection) {
            $editableSection->questions()->delete();
            $editableSection->delete();
        });

        return redirect()
            ->route('questionnaire-builder.edit', $editableTemplate)
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis la section a été archivée.'
                : 'Section archivée.');
    }

    public function storeQuestion(StoreQuestionnaireQuestionRequest $request, QuestionnaireSection $section): RedirectResponse
    {
        $template = $section->template;
        abort_unless($template instanceof QuestionnaireTemplate, 404);
        $this->authorize('update', $template);

        [$editableTemplate, $editableSection, $cloned] = $this->editableSectionContext($section, $request->user());

        DB::transaction(function () use ($editableSection, $request) {
            $validated = $request->validated();
            $editableSection->questions()->create([
                ...$validated,
                'sort_order' => $validated['sort_order'] ?? ($editableSection->questions()->max('sort_order') + 1),
            ]);
        });

        return redirect()
            ->route('questionnaire-builder.edit', $editableTemplate)
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis la question a été ajoutée.'
                : 'Question ajoutée.');
    }

    public function updateQuestion(UpdateQuestionnaireQuestionRequest $request, QuestionnaireQuestion $question): RedirectResponse
    {
        $template = $question->section?->template;
        abort_unless($template instanceof QuestionnaireTemplate, 404);
        $this->authorize('update', $template);

        [$editableTemplate, $editableQuestion, $cloned] = $this->editableQuestionContext($question, $request->user());

        DB::transaction(function () use ($editableQuestion, $request) {
            $editableQuestion->update($request->validated());
        });

        return redirect()
            ->route('questionnaire-builder.edit', $editableTemplate)
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis la question a été mise à jour.'
                : 'Question mise à jour.');
    }

    public function destroyQuestion(Request $request, QuestionnaireQuestion $question): RedirectResponse
    {
        $template = $question->section?->template;
        abort_unless($template instanceof QuestionnaireTemplate, 404);
        $this->authorize('update', $template);

        [$editableTemplate, $editableQuestion, $cloned] = $this->editableQuestionContext($question, $request->user());

        DB::transaction(function () use ($editableQuestion) {
            $editableQuestion->delete();
        });

        return redirect()
            ->route('questionnaire-builder.edit', $editableTemplate)
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis la question a été archivée.'
                : 'Question archivée.');
    }

    public function reorderSections(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'template_id' => ['required', 'exists:questionnaire_templates,id'],
            'section_ids' => ['nullable', 'array', 'min:1'],
            'section_ids.*' => ['integer'],
            'positions' => ['nullable', 'array'],
            'positions.*' => ['integer', 'min:0'],
        ]);

        $template = QuestionnaireTemplate::query()->findOrFail((int) $validated['template_id']);
        $this->authorize('update', $template);

        [$editableTemplate, $cloned] = $this->ensureEditableTemplate($template, $request->user());
        $orderedSectionIds = $this->orderedIdsFromPayload($validated, 'section_ids');
        $sectionIds = $this->mapSectionIdsForEditableTemplate($editableTemplate, $template, $orderedSectionIds);

        DB::transaction(function () use ($sectionIds, $editableTemplate) {
            foreach (array_values($sectionIds) as $index => $sectionId) {
                $editableTemplate->sections()->whereKey($sectionId)->update(['sort_order' => $index]);
            }
        });

        return redirect()
            ->route('questionnaire-builder.edit', $editableTemplate)
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis les sections ont été réordonnées.'
                : 'Sections réordonnées.');
    }

    public function reorderQuestions(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'section_id' => ['required', 'exists:questionnaire_sections,id'],
            'question_ids' => ['nullable', 'array', 'min:1'],
            'question_ids.*' => ['integer'],
            'positions' => ['nullable', 'array'],
            'positions.*' => ['integer', 'min:0'],
        ]);

        $section = QuestionnaireSection::query()->with('template')->findOrFail((int) $validated['section_id']);
        $template = $section->template;
        abort_unless($template instanceof QuestionnaireTemplate, 404);
        $this->authorize('update', $template);

        [$editableTemplate, $editableSection, $cloned] = $this->editableSectionContext($section, $request->user());
        $orderedQuestionIds = $this->orderedIdsFromPayload($validated, 'question_ids');
        $questionIds = $this->mapQuestionIdsForEditableSection($editableSection, $section, $orderedQuestionIds);

        DB::transaction(function () use ($editableSection, $questionIds) {
            foreach (array_values($questionIds) as $index => $questionId) {
                $editableSection->questions()->whereKey($questionId)->update(['sort_order' => $index]);
            }
        });

        return redirect()
            ->route('questionnaire-builder.edit', $editableTemplate)
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis les questions ont été réordonnées.'
                : 'Questions réordonnées.');
    }

    public function publish(Request $request, QuestionnaireTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);
        abort_if($template->mission_id !== null, 403, 'Utilisez le circuit de relecture et d’adoption de la mission.');

        try {
            $published = $this->publishing->publish($template, $request->user());
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('questionnaire-builder.edit', $template)
                ->withErrors(['publish' => $exception->getMessage()]);
        }

        $this->audit->questionnaireTemplatePublished($request->user(), $published, $request);

        return redirect()
            ->route('questionnaire-builder.edit', $published)
            ->with('status', 'Template publié et verrouillé pour édition directe.');
    }

    public function archive(Request $request, QuestionnaireTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);
        if ($template->mission_id !== null) {
            $template->loadMissing('mission');
            abort_unless($template->mission !== null && $request->user()->can('governMission', $template->mission), 403);
        }

        $archived = DB::transaction(fn () => $this->publishing->archive($template, $request->user()));
        $this->audit->questionnaireTemplateArchived($request->user(), $archived, $request);

        return redirect()
            ->route('questionnaire-builder.index')
            ->with('status', 'Template archivé.');
    }

    /**
     * @return array{QuestionnaireTemplate, bool}
     */
    private function ensureEditableTemplate(QuestionnaireTemplate $template, ?\App\Models\User $actor): array
    {
        $template->invalidateReviews();
        $editable = $this->publishing->ensureEditableDraft($template, $actor);

        return [$editable, (int) $editable->id !== (int) $template->id];
    }

    /**
     * @return array{QuestionnaireTemplate, QuestionnaireSection, bool}
     */
    private function editableSectionContext(QuestionnaireSection $section, ?\App\Models\User $actor): array
    {
        $template = $section->template;
        abort_unless($template instanceof QuestionnaireTemplate, 404);

        [$editableTemplate, $cloned] = $this->ensureEditableTemplate($template, $actor);
        if (! $cloned) {
            return [$editableTemplate, $section, false];
        }

        $editableSection = $editableTemplate->sections()
            ->where('source_section_id', $section->id)
            ->firstOrFail();

        return [$editableTemplate, $editableSection, true];
    }

    /**
     * @return array{QuestionnaireTemplate, QuestionnaireQuestion, bool}
     */
    private function editableQuestionContext(QuestionnaireQuestion $question, ?\App\Models\User $actor): array
    {
        $section = $question->section;
        abort_unless($section instanceof QuestionnaireSection, 404);

        [$editableTemplate, $editableSection, $cloned] = $this->editableSectionContext($section, $actor);
        if (! $cloned) {
            return [$editableTemplate, $question, false];
        }

        $editableQuestion = $editableSection->questions()
            ->where('source_question_id', $question->id)
            ->firstOrFail();

        return [$editableTemplate, $editableQuestion, true];
    }

    /**
     * @param  list<int>  $sectionIds
     * @return list<int>
     */
    private function mapSectionIdsForEditableTemplate(
        QuestionnaireTemplate $editableTemplate,
        QuestionnaireTemplate $originalTemplate,
        array $sectionIds,
    ): array {
        if ((int) $editableTemplate->id === (int) $originalTemplate->id) {
            return array_map('intval', $sectionIds);
        }

        return $editableTemplate->sections()
            ->whereIn('source_section_id', array_map('intval', $sectionIds))
            ->get()
            ->sortBy(fn (QuestionnaireSection $section) => array_search($section->source_section_id, $sectionIds, true))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $questionIds
     * @return list<int>
     */
    private function mapQuestionIdsForEditableSection(
        QuestionnaireSection $editableSection,
        QuestionnaireSection $originalSection,
        array $questionIds,
    ): array {
        if ((int) $editableSection->id === (int) $originalSection->id) {
            return array_map('intval', $questionIds);
        }

        return $editableSection->questions()
            ->whereIn('source_question_id', array_map('intval', $questionIds))
            ->get()
            ->sortBy(fn (QuestionnaireQuestion $question) => array_search($question->source_question_id, $questionIds, true))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function lineageTemplates(QuestionnaireTemplate $template)
    {
        $rootId = (int) ($template->source_template_id ?: $template->id);

        return QuestionnaireTemplate::query()
            ->where(function ($query) use ($rootId) {
                $query->whereKey($rootId)
                    ->orWhere('source_template_id', $rootId);
            })
            ->withCount('sections')
            ->orderByDesc('version')
            ->get();
    }

    /** @param array<string, mixed> $validated */
    private function validatedParentId(
        QuestionnaireTemplate $template,
        array $validated,
        ?QuestionnaireSection $section = null,
    ): ?int {
        $parentId = isset($validated['parent_section_id']) ? (int) $validated['parent_section_id'] : null;
        $type = $validated['section_type'] ?? QuestionnaireSection::TYPE_THEME;

        if ($type === QuestionnaireSection::TYPE_THEME) {
            return null;
        }

        abort_if($parentId === null, 422, 'Une structure parente est obligatoire.');
        abort_if($section && $parentId === (int) $section->id, 422, 'Une structure ne peut pas être sa propre parente.');

        $parent = $template->sections()
            ->where(function ($query) use ($parentId): void {
                $query->whereKey($parentId)->orWhere('source_section_id', $parentId);
            })
            ->firstOrFail();
        $expectedParentType = $type === QuestionnaireSection::TYPE_THEMATIC
            ? QuestionnaireSection::TYPE_THEME
            : QuestionnaireSection::TYPE_THEMATIC;
        abort_unless($parent->section_type === $expectedParentType, 422, 'Le niveau de la structure parente est invalide.');

        return $parent->id;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return list<int>
     */
    private function orderedIdsFromPayload(array $validated, string $idsKey): array
    {
        $ids = array_map('intval', $validated[$idsKey] ?? []);
        if ($ids !== []) {
            return $ids;
        }

        $positions = collect($validated['positions'] ?? [])
            ->mapWithKeys(fn ($position, $id) => [(int) $id => (int) $position])
            ->sortBy(fn ($position) => $position)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        abort_if($positions === [], 422, 'Ordre invalide.');

        return $positions;
    }
}
