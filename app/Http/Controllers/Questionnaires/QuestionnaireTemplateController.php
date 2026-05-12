<?php

namespace App\Http\Controllers\Questionnaires;

use App\Http\Controllers\Controller;
use App\Http\Requests\Questionnaires\StoreQuestionnaireQuestionRequest;
use App\Http\Requests\Questionnaires\StoreQuestionnaireSectionRequest;
use App\Http\Requests\Questionnaires\StoreQuestionnaireTemplateRequest;
use App\Http\Requests\Questionnaires\UpdateQuestionnaireTemplateRequest;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use App\Models\QuestionnaireTemplate;
use App\Services\Iam\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class QuestionnaireTemplateController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $query = QuestionnaireTemplate::query()->withCount('sections')->orderBy('name');

        if (! $user->canManageQuestionnaireLibrary()) {
            $query->where('active', true)
                ->where(function ($q) use ($user) {
                    $q->whereNull('department_scope')
                        ->orWhereJsonLength('department_scope', 0);
                    if ($user->department_id !== null) {
                        $q->orWhereJsonContains('department_scope', (int) $user->department_id);
                    }
                });
        }

        $templates = $query->paginate(20)->withQueryString();

        return view('questionnaires.templates.index', compact('templates'));
    }

    public function create(): View
    {
        $this->authorize('create', QuestionnaireTemplate::class);

        return view('questionnaires.templates.create');
    }

    public function store(StoreQuestionnaireTemplateRequest $request): RedirectResponse
    {
        $template = QuestionnaireTemplate::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        app(SecurityAuditService::class)->questionnaireTemplateCreated($request->user(), $template, $request);

        return redirect()
            ->route('questionnaire-templates.edit', $template)
            ->with('status', 'Modèle de questionnaire créé.');
    }

    public function edit(QuestionnaireTemplate $questionnaire_template): View
    {
        $this->authorize('view', $questionnaire_template);

        $questionnaire_template->load(['sections.questions' => fn ($q) => $q->orderBy('sort_order')]);

        return view('questionnaires.templates.edit', [
            'template' => $questionnaire_template,
        ]);
    }

    public function update(UpdateQuestionnaireTemplateRequest $request, QuestionnaireTemplate $questionnaire_template): RedirectResponse
    {
        $questionnaire_template->update([
            ...$request->validated(),
            'updated_by' => $request->user()?->id,
        ]);

        app(SecurityAuditService::class)->questionnaireTemplateUpdated($request->user(), $questionnaire_template->fresh(), $request);

        return redirect()
            ->route('questionnaire-templates.edit', $questionnaire_template)
            ->with('status', 'Modèle mis à jour.');
    }

    public function destroy(Request $request, QuestionnaireTemplate $questionnaire_template): RedirectResponse
    {
        $this->authorize('delete', $questionnaire_template);

        $questionnaire_template->delete();

        return redirect()
            ->route('questionnaire-templates.index')
            ->with('status', 'Modèle archivé (soft delete).');
    }

    public function duplicate(Request $request, QuestionnaireTemplate $questionnaire_template): RedirectResponse
    {
        $this->authorize('duplicate', $questionnaire_template);

        $copy = DB::transaction(function () use ($request, $questionnaire_template) {
            $questionnaire_template->load('sections.questions');

            $slug = Str::slug($questionnaire_template->name).'-copie-'.Str::lower(Str::random(5));
            while (QuestionnaireTemplate::query()->where('slug', $slug)->exists()) {
                $slug = Str::slug($questionnaire_template->name).'-copie-'.Str::lower(Str::random(5));
            }

            $new = QuestionnaireTemplate::query()->create([
                'name' => $questionnaire_template->name.' (copie)',
                'slug' => $slug,
                'description' => $questionnaire_template->description,
                'mission_type' => $questionnaire_template->mission_type,
                'department_scope' => $questionnaire_template->department_scope,
                'active' => false,
                'version' => 1,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            foreach ($questionnaire_template->sections as $section) {
                $sec = QuestionnaireSection::query()->create([
                    'questionnaire_template_id' => $new->id,
                    'title' => $section->title,
                    'description' => $section->description,
                    'sort_order' => $section->sort_order,
                ]);

                foreach ($section->questions as $question) {
                    QuestionnaireQuestion::query()->create([
                        'questionnaire_section_id' => $sec->id,
                        'code' => $question->code,
                        'question' => $question->question,
                        'help_text' => $question->help_text,
                        'question_type' => $question->question_type,
                        'required' => $question->required,
                        'allows_observation' => $question->allows_observation,
                        'allows_risk_detection' => $question->allows_risk_detection,
                        'expected_documents' => $question->expected_documents,
                        'risk_category' => $question->risk_category,
                        'risk_level' => $question->risk_level,
                        'sort_order' => $question->sort_order,
                        'active' => $question->active,
                        'metadata' => $question->metadata,
                    ]);
                }
            }

            return $new;
        });

        app(SecurityAuditService::class)->questionnaireTemplateCreated($request->user(), $copy, $request);

        return redirect()
            ->route('questionnaire-templates.edit', $copy)
            ->with('status', 'Modèle dupliqué (inactif par défaut).');
    }

    public function storeSection(StoreQuestionnaireSectionRequest $request, QuestionnaireTemplate $questionnaire_template): RedirectResponse
    {
        $questionnaire_template->sections()->create($request->validated());

        return back()->with('status', 'Section ajoutée.');
    }

    public function destroySection(Request $request, QuestionnaireTemplate $questionnaire_template, QuestionnaireSection $section): RedirectResponse
    {
        $this->authorize('update', $questionnaire_template);
        abort_unless((int) $section->questionnaire_template_id === (int) $questionnaire_template->id, 404);

        $section->delete();

        return back()->with('status', 'Section supprimée.');
    }

    public function storeQuestion(
        StoreQuestionnaireQuestionRequest $request,
        QuestionnaireTemplate $questionnaire_template,
        QuestionnaireSection $section
    ): RedirectResponse {
        abort_unless((int) $section->questionnaire_template_id === (int) $questionnaire_template->id, 404);

        $data = $request->validated();
        $data['required'] = $request->boolean('required');
        $data['allows_observation'] = $request->boolean('allows_observation');
        $data['allows_risk_detection'] = $request->boolean('allows_risk_detection');

        $section->questions()->create($data);

        return back()->with('status', 'Question ajoutée.');
    }

    public function destroyQuestion(
        Request $request,
        QuestionnaireTemplate $questionnaire_template,
        QuestionnaireSection $section,
        QuestionnaireQuestion $question
    ): RedirectResponse {
        $this->authorize('update', $questionnaire_template);
        abort_unless((int) $section->questionnaire_template_id === (int) $questionnaire_template->id, 404);
        abort_unless((int) $question->questionnaire_section_id === (int) $section->id, 404);

        $question->delete();

        return back()->with('status', 'Question archivée.');
    }
}
