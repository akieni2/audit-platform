<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\MissionTeamMember;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use App\Models\QuestionnaireTemplate;
use App\Models\QuestionnaireTemplateReview;
use App\Services\Questionnaires\QuestionnairePublishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class MissionQuestionnaireWizardController extends Controller
{
    public function index(Mission $mission): View
    {
        $this->authorize('view', $mission);
        $mission->load([
            'questionnaireTemplates.creator',
            'questionnaireTemplates.sections',
            'questionnaireTemplates.reviews.reviewer',
            'questionnaireTemplates.adopter',
            'auditGroups.questionnaireTemplate',
            'auditGroups.members.user',
        ]);

        return view('missions.questionnaires-index', compact('mission'));
    }

    public function create(Mission $mission): View
    {
        $this->authorize('createQuestionnaire', $mission);

        return view('missions.questionnaire-wizard', compact('mission'));
    }

    public function store(
        Request $request,
        Mission $mission,
    ): RedirectResponse {
        $this->authorize('createQuestionnaire', $mission);

        $validated = $request->validate([
            'structure' => ['required', 'string', 'max:1000000'],
        ]);
        $structure = json_decode($validated['structure'], true);

        if (! is_array($structure)) {
            throw ValidationException::withMessages(['structure' => 'La structure du questionnaire est invalide.']);
        }

        $this->validateStructure($structure);

        $template = DB::transaction(function () use ($structure, $mission, $request): QuestionnaireTemplate {
            $themeTitle = trim((string) $structure['theme']);
            $template = QuestionnaireTemplate::query()->create([
                'name' => $themeTitle.' — '.$mission->organisation,
                'slug' => Str::slug($themeTitle).'-mission-'.$mission->id.'-'.Str::lower(Str::random(6)),
                'description' => 'Questionnaire créé visuellement pour la mission '.$mission->organisation.'.',
                'mission_id' => $mission->id,
                'department_scope' => $mission->department_id ? [(int) $mission->department_id] : null,
                'visibility_scope' => 'mission',
                'sharing_mode' => 'private',
                'is_global_template' => false,
                'is_private_template' => true,
                'active' => false,
                'version' => 1,
                'lifecycle_status' => QuestionnaireTemplate::STATUS_DRAFT,
                'review_status' => QuestionnaireTemplate::REVIEW_DRAFT,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            $theme = $this->createSection($template, $themeTitle, QuestionnaireSection::TYPE_THEME, null, 1);
            foreach ($structure['thematics'] as $thematicIndex => $thematicData) {
                $thematic = $this->createSection(
                    $template,
                    trim((string) $thematicData['title']),
                    QuestionnaireSection::TYPE_THEMATIC,
                    $theme->id,
                    $thematicIndex + 1,
                );

                foreach ($thematicData['subthemes'] as $subthemeIndex => $subthemeData) {
                    $subtheme = $this->createSection(
                        $template,
                        trim((string) $subthemeData['title']),
                        QuestionnaireSection::TYPE_SUBTHEME,
                        $thematic->id,
                        $subthemeIndex + 1,
                    );

                    foreach ($subthemeData['questions'] as $questionIndex => $questionData) {
                        QuestionnaireQuestion::query()->create([
                            'questionnaire_section_id' => $subtheme->id,
                            'code' => sprintf('T%d-ST%d-Q%d', $thematicIndex + 1, $subthemeIndex + 1, $questionIndex + 1),
                            'question' => trim((string) $questionData['question']),
                            'help_text' => $this->nullableText($questionData['help_text'] ?? null),
                            'question_type' => in_array($questionData['question_type'] ?? null, QuestionnaireQuestion::questionTypes(), true)
                                ? $questionData['question_type']
                                : QuestionnaireQuestion::TYPE_TEXTAREA,
                            'required' => (bool) ($questionData['required'] ?? true),
                            'allows_observation' => (bool) ($questionData['allows_observation'] ?? true),
                            'allows_risk_detection' => (bool) ($questionData['allows_risk_detection'] ?? true),
                            'expected_documents' => $this->nullableText($questionData['expected_documents'] ?? null),
                            'sort_order' => $questionIndex + 1,
                            'active' => true,
                        ]);
                    }
                }
            }

            return $template->fresh(['sections.questions']);
        });

        return redirect()
            ->route('questionnaire-builder.edit', $template)
            ->with('status', 'Brouillon collaboratif créé. Les inspecteurs peuvent maintenant le relire et le modifier avant adoption.');
    }

    public function submitReview(
        Request $request,
        Mission $mission,
        QuestionnaireTemplate $template,
        QuestionnairePublishingService $publishing,
    ): RedirectResponse {
        $this->authorizeTemplate($mission, $template);
        $this->authorize('update', $template);
        try {
            $publishing->validateStructure($template);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['structure' => $exception->getMessage()]);
        }

        DB::transaction(function () use ($template, $request): void {
            $template->reviews()->delete();
            QuestionnaireTemplateReview::query()->create([
                'questionnaire_template_id' => $template->id,
                'reviewer_id' => $request->user()->id,
                'decision' => QuestionnaireTemplateReview::DECISION_APPROVED,
                'comment' => 'Version proposée pour adoption collective.',
            ]);
            $template->forceFill([
                'review_status' => QuestionnaireTemplate::REVIEW_IN_REVIEW,
                'review_requested_at' => now(),
            ])->saveQuietly();
        });

        return back()->with('status', 'Questionnaire soumis à la relecture des autres inspecteurs.');
    }

    public function review(Request $request, Mission $mission, QuestionnaireTemplate $template): RedirectResponse
    {
        $this->authorizeTemplate($mission, $template);
        $this->authorize('createQuestionnaire', $mission);
        abort_unless($template->review_status === QuestionnaireTemplate::REVIEW_IN_REVIEW, 422);

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,changes_requested'],
            'comment' => ['nullable', 'string', 'max:3000', 'required_if:decision,changes_requested'],
        ]);

        QuestionnaireTemplateReview::query()->updateOrCreate(
            ['questionnaire_template_id' => $template->id, 'reviewer_id' => $request->user()->id],
            ['decision' => $validated['decision'], 'comment' => $validated['comment'] ?? null],
        );

        return back()->with('status', $validated['decision'] === QuestionnaireTemplateReview::DECISION_APPROVED
            ? 'Votre approbation a été enregistrée.'
            : 'Votre demande de modification a été enregistrée.');
    }

    public function adopt(
        Request $request,
        Mission $mission,
        QuestionnaireTemplate $template,
        QuestionnairePublishingService $publishing,
    ): RedirectResponse {
        $this->authorizeTemplate($mission, $template);
        $this->authorize('governMission', $mission);
        abort_unless($template->review_status === QuestionnaireTemplate::REVIEW_IN_REVIEW, 422);

        $approvedReviewerIds = $template->reviews()
            ->where('decision', QuestionnaireTemplateReview::DECISION_APPROVED)
            ->pluck('reviewer_id')
            ->map(fn ($id) => (int) $id)
            ->unique();
        $requiredReviewerIds = $mission->missionTeamMembers()
            ->whereIn('mission_role', [
                MissionTeamMember::ROLE_CHEF_MISSION,
                MissionTeamMember::ROLE_INSPECTEUR_VERIFICATEUR,
                MissionTeamMember::ROLE_INSPECTEUR_VERIFICATEUR_ADJOINT,
            ])
            ->pluck('user_id')
            ->push((int) $template->created_by)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();
        $changesRequested = $template->reviews()->where('decision', QuestionnaireTemplateReview::DECISION_CHANGES_REQUESTED)->exists();
        $missingRequiredApproval = $requiredReviewerIds->diff($approvedReviewerIds)->isNotEmpty();
        if ($approvedReviewerIds->count() < 2 || $missingRequiredApproval || $changesRequested) {
            throw ValidationException::withMessages([
                'adoption' => 'L’adoption exige l’approbation du créateur, de tous les inspecteurs affectés à la mission, au moins deux avis distincts et aucune demande de modification en attente.',
            ]);
        }

        $published = $publishing->publish($template, $request->user());
        $published->forceFill([
            'review_status' => QuestionnaireTemplate::REVIEW_ADOPTED,
            'adopted_at' => now(),
            'adopted_by' => $request->user()->id,
        ])->saveQuietly();

        return redirect()->route('missions.show', $mission)
            ->with('status', 'Version finale adoptée et disponible pour les groupes d’audit.');
    }

    private function authorizeTemplate(Mission $mission, QuestionnaireTemplate $template): void
    {
        abort_unless((int) $template->mission_id === (int) $mission->id, 404);
    }

    private function createSection(
        QuestionnaireTemplate $template,
        string $title,
        string $type,
        ?int $parentId,
        int $order,
    ): QuestionnaireSection {
        return QuestionnaireSection::query()->create([
            'questionnaire_template_id' => $template->id,
            'title' => $title,
            'section_type' => $type,
            'parent_section_id' => $parentId,
            'sort_order' => $order,
        ]);
    }

    /** @param array<string, mixed> $structure */
    private function validateStructure(array $structure): void
    {
        $theme = trim((string) ($structure['theme'] ?? ''));
        if ($theme === '' || Str::length($theme) > 255) {
            throw ValidationException::withMessages(['structure' => 'Le thème est obligatoire.']);
        }

        if (! isset($structure['thematics']) || ! is_array($structure['thematics']) || $structure['thematics'] === [] || count($structure['thematics']) > 30) {
            throw ValidationException::withMessages(['structure' => 'Ajoutez au moins une thématique.']);
        }

        foreach ($structure['thematics'] as $thematic) {
            if (! is_array($thematic) || trim((string) ($thematic['title'] ?? '')) === '' || Str::length((string) $thematic['title']) > 255) {
                throw ValidationException::withMessages(['structure' => 'Chaque thématique doit avoir un titre.']);
            }
            if (! isset($thematic['subthemes']) || ! is_array($thematic['subthemes']) || $thematic['subthemes'] === [] || count($thematic['subthemes']) > 50) {
                throw ValidationException::withMessages(['structure' => 'Chaque thématique doit contenir au moins une sous-thématique.']);
            }
            foreach ($thematic['subthemes'] as $subtheme) {
                if (! is_array($subtheme) || trim((string) ($subtheme['title'] ?? '')) === '' || Str::length((string) $subtheme['title']) > 255) {
                    throw ValidationException::withMessages(['structure' => 'Chaque sous-thématique doit avoir un titre.']);
                }
                if (! isset($subtheme['questions']) || ! is_array($subtheme['questions']) || $subtheme['questions'] === [] || count($subtheme['questions']) > 200) {
                    throw ValidationException::withMessages(['structure' => 'Chaque sous-thématique doit contenir au moins une question.']);
                }
                foreach ($subtheme['questions'] as $question) {
                    if (! is_array($question) || trim((string) ($question['question'] ?? '')) === '') {
                        throw ValidationException::withMessages(['structure' => 'Le texte de chaque question est obligatoire.']);
                    }
                }
            }
        }
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : null;
    }
}
