<?php

namespace App\Http\Controllers;

use App\Http\Requests\Questionnaires\StoreEntretienDynamicResponsesRequest;
use App\Models\Entretien;
use App\Models\EntretienResponse;
use App\Models\IdentifiedRisk;
use App\Models\QuestionnaireQuestion;
use App\Services\Iam\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EntretienConduiteController extends Controller
{
    public function show(Entretien $entretien): View
    {
        $this->authorize('conductQuestionnaire', $entretien);

        $entretien->load([
            'mission',
            'service',
            'questionnaireTemplate.sections.questions' => fn ($q) => $q->where('active', true)->orderBy('sort_order'),
        ]);

        abort_unless($entretien->questionnaire_template_id !== null, 404);

        $template = $entretien->questionnaireTemplate;
        abort_unless($template !== null && $template->active, 404);

        $existing = $entretien->questionnaireResponses()
            ->get()
            ->keyBy('questionnaire_question_id');

        return view('entretiens.conduite', [
            'entretien' => $entretien,
            'template' => $template,
            'existingResponses' => $existing,
        ]);
    }

    public function storeResponses(StoreEntretienDynamicResponsesRequest $request, Entretien $entretien): RedirectResponse
    {
        $entretien->load([
            'mission',
            'service',
            'questionnaireTemplate.sections.questions' => fn ($q) => $q->where('active', true)->orderBy('sort_order'),
        ]);

        $allowedIds = $entretien->questionnaireTemplate?->sections
            ->flatMap(fn ($s) => $s->questions->pluck('id'))
            ->all() ?? [];

        $audit = app(SecurityAuditService::class);
        $user = $request->user();

        foreach ($request->validated('responses') as $row) {
            $qid = (int) $row['questionnaire_question_id'];
            if (! in_array($qid, $allowedIds, true)) {
                abort(422, 'Question hors modèle attaché à l’entretien.');
            }

            $question = QuestionnaireQuestion::query()->findOrFail($qid);

            $payload = [
                'answer_boolean' => array_key_exists('answer_boolean', $row) ? $row['answer_boolean'] : null,
                'answer_text' => $row['answer_text'] ?? null,
                'answer_json' => $row['answer_json'] ?? null,
                'observation' => $row['observation'] ?? null,
                'uploaded_documents_metadata' => $row['uploaded_documents_metadata'] ?? null,
                'detected_risk' => $row['detected_risk'] ?? null,
                'created_by' => $user?->id,
            ];

            $response = EntretienResponse::query()->updateOrCreate(
                [
                    'entretien_id' => $entretien->id,
                    'questionnaire_question_id' => $qid,
                ],
                $payload
            );

            $audit->entretienResponseCreated($user, $entretien, $response, $request);

            if (! empty($row['identified_risk']) && is_array($row['identified_risk']) && $question->allows_risk_detection) {
                $ir = $row['identified_risk'];
                if (empty($ir['title']) || ! is_string($ir['title'])) {
                    continue;
                }
                $risk = IdentifiedRisk::query()->create([
                    'mission_id' => $entretien->mission_id,
                    'service_id' => $entretien->service_id,
                    'entretien_id' => $entretien->id,
                    'questionnaire_question_id' => $qid,
                    'title' => $ir['title'],
                    'description' => $ir['description'] ?? null,
                    'category' => $ir['category'] ?? $question->risk_category,
                    'probability' => $ir['probability'] ?? null,
                    'impact' => $ir['impact'] ?? null,
                    'criticality' => $ir['criticality'] ?? null,
                    'recommendation' => $ir['recommendation'] ?? null,
                    'ai_generated' => false,
                    'validated_by_human' => false,
                    'created_by' => $user?->id,
                ]);

                $audit->riskIdentified($user, $risk, $request);
            }
        }

        return redirect()
            ->route('entretiens.conduite.show', $entretien)
            ->with('status', 'Réponses enregistrées.');
    }
}
