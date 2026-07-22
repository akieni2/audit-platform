<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use App\Models\QuestionnaireTemplate;
use App\Services\Questionnaires\QuestionnairePublishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MissionQuestionnaireWizardController extends Controller
{
    public function create(Mission $mission): View
    {
        $this->authorize('assignTeamMembers', $mission);

        return view('missions.questionnaire-wizard', compact('mission'));
    }

    public function store(
        Request $request,
        Mission $mission,
        QuestionnairePublishingService $publishing,
    ): RedirectResponse {
        $this->authorize('assignTeamMembers', $mission);

        $validated = $request->validate([
            'structure' => ['required', 'string', 'max:1000000'],
        ]);
        $structure = json_decode($validated['structure'], true);

        if (! is_array($structure)) {
            throw ValidationException::withMessages(['structure' => 'La structure du questionnaire est invalide.']);
        }

        $this->validateStructure($structure);

        $template = DB::transaction(function () use ($structure, $mission, $request, $publishing): QuestionnaireTemplate {
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

            return $publishing->publish($template, $request->user());
        });

        return redirect()
            ->route('missions.show', $mission)
            ->with('status', 'Questionnaire « '.$template->name.' » créé et mis à la disposition des équipes de la mission.');
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
