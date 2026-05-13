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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuestionnaireTemplateController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('questionnaire-builder.index');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('questionnaire-builder.index');
    }

    public function store(StoreQuestionnaireTemplateRequest $request): RedirectResponse
    {
        $template = QuestionnaireTemplate::query()->create([
            ...$request->validated(),
            'active' => false,
            'lifecycle_status' => QuestionnaireTemplate::STATUS_DRAFT,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        app(SecurityAuditService::class)->questionnaireTemplateCreated($request->user(), $template, $request);

        return redirect()
            ->route('questionnaire-builder.edit', $template)
            ->with('status', 'Modèle créé dans le builder officiel.');
    }

    public function edit(QuestionnaireTemplate $questionnaire_template): RedirectResponse
    {
        return redirect()->route('questionnaire-builder.edit', $questionnaire_template);
    }

    public function update(UpdateQuestionnaireTemplateRequest $request, QuestionnaireTemplate $questionnaire_template): RedirectResponse
    {
        if ($questionnaire_template->isImmutable()) {
            return redirect()
                ->route('questionnaire-builder.edit', $questionnaire_template)
                ->with('status', 'Utilisez le builder officiel pour créer une nouvelle version brouillon.');
        }

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
        if ($questionnaire_template->isImmutable()) {
            return redirect()
                ->route('questionnaire-builder.edit', $questionnaire_template)
                ->with('status', 'L’archivage d’un template publié se fait depuis le builder officiel.');
        }

        $this->authorize('delete', $questionnaire_template);

        DB::transaction(function () use ($questionnaire_template) {
            $questionnaire_template->load('sections.questions');

            foreach ($questionnaire_template->sections as $section) {
                $section->questions()->delete();
                $section->delete();
            }

            $questionnaire_template->delete();
        });

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
                'lifecycle_status' => QuestionnaireTemplate::STATUS_DRAFT,
                'source_template_id' => $questionnaire_template->source_template_id ?: $questionnaire_template->id,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            foreach ($questionnaire_template->sections as $section) {
                $sec = QuestionnaireSection::query()->create([
                    'questionnaire_template_id' => $new->id,
                    'title' => $section->title,
                    'description' => $section->description,
                    'sort_order' => $section->sort_order,
                    'source_section_id' => $section->id,
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
                        'source_question_id' => $question->id,
                    ]);
                }
            }

            return $new;
        });

        app(SecurityAuditService::class)->questionnaireTemplateCreated($request->user(), $copy, $request);

        return redirect()
            ->route('questionnaire-builder.edit', $copy)
            ->with('status', 'Modèle dupliqué dans le builder officiel.');
    }

    public function storeSection(StoreQuestionnaireSectionRequest $request, QuestionnaireTemplate $questionnaire_template): RedirectResponse
    {
        if ($questionnaire_template->isImmutable()) {
            return redirect()
                ->route('questionnaire-builder.edit', $questionnaire_template)
                ->with('status', 'Les templates publiés se modifient via une nouvelle version brouillon dans le builder officiel.');
        }

        $questionnaire_template->sections()->create($request->validated());

        return back()->with('status', 'Section ajoutée.');
    }

    public function destroySection(Request $request, QuestionnaireTemplate $questionnaire_template, QuestionnaireSection $section): RedirectResponse
    {
        if ($questionnaire_template->isImmutable()) {
            return redirect()
                ->route('questionnaire-builder.edit', $questionnaire_template)
                ->with('status', 'Les templates publiés se modifient via une nouvelle version brouillon dans le builder officiel.');
        }

        $this->authorize('update', $questionnaire_template);
        abort_unless((int) $section->questionnaire_template_id === (int) $questionnaire_template->id, 404);

        DB::transaction(function () use ($section) {
            $section->questions()->delete();
            $section->delete();
        });

        return back()->with('status', 'Section archivée.');
    }

    public function storeQuestion(
        StoreQuestionnaireQuestionRequest $request,
        QuestionnaireTemplate $questionnaire_template,
        QuestionnaireSection $section
    ): RedirectResponse {
        if ($questionnaire_template->isImmutable()) {
            return redirect()
                ->route('questionnaire-builder.edit', $questionnaire_template)
                ->with('status', 'Les templates publiés se modifient via une nouvelle version brouillon dans le builder officiel.');
        }

        abort_unless((int) $section->questionnaire_template_id === (int) $questionnaire_template->id, 404);

        $data = $request->validated();

        $section->questions()->create($data);

        return back()->with('status', 'Question ajoutée.');
    }

    public function destroyQuestion(
        Request $request,
        QuestionnaireTemplate $questionnaire_template,
        QuestionnaireSection $section,
        QuestionnaireQuestion $question
    ): RedirectResponse {
        if ($questionnaire_template->isImmutable()) {
            return redirect()
                ->route('questionnaire-builder.edit', $questionnaire_template)
                ->with('status', 'Les templates publiés se modifient via une nouvelle version brouillon dans le builder officiel.');
        }

        $this->authorize('update', $questionnaire_template);
        abort_unless((int) $section->questionnaire_template_id === (int) $questionnaire_template->id, 404);
        abort_unless((int) $question->questionnaire_section_id === (int) $section->id, 404);

        $question->delete();

        return back()->with('status', 'Question archivée.');
    }
}
