<?php

namespace App\Http\Controllers;

use App\Http\Requests\Questionnaires\StoreEntretienDynamicResponsesRequest;
use App\Models\Entretien;
use App\Services\Questionnaires\QuestionnaireRuntimeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class EntretienConduiteController extends Controller
{
    public function show(Entretien $entretien): View
    {
        $this->authorize('conductQuestionnaire', $entretien);

        abort_unless($entretien->questionnaire_template_id !== null || ! empty($entretien->questionnaire_snapshot), 404);

        try {
            $runtime = app(QuestionnaireRuntimeService::class)->buildViewData($entretien);
        } catch (InvalidArgumentException $exception) {
            abort(404, $exception->getMessage());
        }

        return view('entretiens.conduite', [
            'entretien' => $entretien,
            'template' => $runtime['template'],
            'existingResponses' => $runtime['existingResponses'],
            'progressPercent' => $runtime['progressPercent'],
        ]);
    }

    public function storeResponses(StoreEntretienDynamicResponsesRequest $request, Entretien $entretien): RedirectResponse
    {
        try {
            app(QuestionnaireRuntimeService::class)->recordResponses(
                $entretien,
                $request->validated('responses'),
                $request->user(),
                $request,
            );
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        return redirect()
            ->route('entretiens.conduite.show', $entretien)
            ->with('status', 'Réponses enregistrées.');
    }
}
