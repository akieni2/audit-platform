<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesVisibleResources;
use App\Models\Entretien;
use App\Models\Question;
use App\Models\QuestionnaireTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EntretienController extends Controller
{
    use ResolvesVisibleResources;

    public function index(int $id): View
    {
        $service = $this->visibleService($id);
        $service->load('mission');

        $mission = $service->mission;

        $entretiens = Entretien::query()
            ->where('service_id', $service->id)
            ->with('questionnaireTemplate')
            ->orderByDesc('id')
            ->get();

        $questions = Question::query()->orderBy('id')->get();

        $templateChoices = collect();
        if ($mission !== null && Auth::user()?->can('governMission', $mission)) {
            $deptId = $mission->department_id !== null ? (int) $mission->department_id : null;
            $templateChoices = QuestionnaireTemplate::query()
                ->where('active', true)
                ->where(function ($q) use ($deptId) {
                    $q->whereNull('department_scope')
                        ->orWhereJsonLength('department_scope', 0);
                    if ($deptId !== null) {
                        $q->orWhereJsonContains('department_scope', $deptId);
                    }
                })
                ->orderBy('name')
                ->get();
        }

        return view('entretiens.index', compact('service', 'entretiens', 'questions', 'templateChoices', 'mission'));
    }

    public function store(Request $request): RedirectResponse
    {
        $service = $this->visibleService((int) $request->service_id);
        $mission = $service->mission;
        abort_unless($mission !== null, 422);

        $templateId = $request->input('questionnaire_template_id');
        if ($templateId !== null && $templateId !== '') {
            if (! Auth::user()?->can('governMission', $mission)) {
                abort(403);
            }
            $tpl = QuestionnaireTemplate::query()
                ->whereKey((int) $templateId)
                ->where('active', true)
                ->firstOrFail();
            abort_unless(
                $tpl->isVisibleToDepartment($mission->department_id !== null ? (int) $mission->department_id : null),
                403
            );
        }

        Entretien::query()->create([
            'mission_id' => $service->mission_id,
            'service_id' => $service->id,
            'questionnaire_template_id' => $templateId !== null && $templateId !== '' ? (int) $templateId : null,
            'responsable_nom' => $request->responsable_nom,
            'role' => $request->role,
            'chef_hierarchique' => $request->chef_hierarchique,
            'auditeur' => $request->auditeur,
            'date_entretien' => $request->date_entretien,
            'notes' => $request->notes,
        ]);

        return back()->with('status', 'Entretien enregistré.');
    }

    public function attachTemplate(Request $request, Entretien $entretien): RedirectResponse
    {
        $this->authorize('attachTemplate', $entretien);

        $validated = $request->validate([
            'questionnaire_template_id' => ['required', 'exists:questionnaire_templates,id'],
        ]);

        $entretien->loadMissing('mission');
        $mission = $entretien->mission;
        abort_unless($mission !== null, 422);

        $tpl = QuestionnaireTemplate::query()->findOrFail((int) $validated['questionnaire_template_id']);
        abort_unless($tpl->active, 422);
        abort_unless(
            $tpl->isVisibleToDepartment($mission->department_id !== null ? (int) $mission->department_id : null),
            403
        );

        $entretien->update(['questionnaire_template_id' => $tpl->id]);

        return back()->with('status', 'Modèle de questionnaire rattaché.');
    }
}
