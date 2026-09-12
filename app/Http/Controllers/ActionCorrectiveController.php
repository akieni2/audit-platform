<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesVisibleResources;
use App\Models\ActionCorrective;
use App\Models\MissionDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActionCorrectiveController extends Controller
{
    use ResolvesVisibleResources;

    public function index(int $id): View
    {
        $risque = $this->visibleRisque($id);

        $actions = ActionCorrective::where('risque_id', $risque->id)->get();

        return view('actions.index', compact('risque', 'actions'));
    }

    public function store(Request $request)
    {
        $risque = $this->visibleRisque((int) $request->risque_id);

        ActionCorrective::create([
            'risque_id' => $risque->id,
            'description' => $request->description,
            'responsable' => $request->responsable,
            'date_echeance' => $request->date_echeance,
            'statut' => 'ouvert',
        ]);

        return back();
    }

    public function updateFollowUp(Request $request, ActionCorrective $action): RedirectResponse
    {
        $action = $this->visibleAction($request, $action);
        $mission = $action->auditRecommendation?->mission;
        abort_unless($action->owner_user_id === $request->user()->id || ($mission && $request->user()->isMissionOperationalContributor($mission)) || ($mission && $request->user()->canGovernMissionInstitutionally($mission)), 403);
        $data = $request->validate([
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'statut' => ['required', Rule::in(['ouvert', 'en_cours', 'bloque'])],
            'comment' => ['nullable', 'string', 'max:10000'],
            'evidence_ids' => ['nullable', 'array'],
            'evidence_ids.*' => ['integer', 'exists:mission_documents,id'],
        ]);
        DB::transaction(function () use ($action, $data, $request, $mission): void {
            $action->update([
                'progress_percent' => $data['progress_percent'], 'statut' => $data['statut'],
                'started_at' => $action->started_at ?? now(),
            ]);
            $action->updates()->create(['user_id' => $request->user()->id, 'progress_percent' => $data['progress_percent'], 'status' => $data['statut'], 'comment' => $data['comment'] ?? null]);
            if ($mission && ! empty($data['evidence_ids'])) {
                $documents = MissionDocument::query()->where('mission_id', $mission->id)->whereIn('id', $data['evidence_ids'])->pluck('id');
                $action->evidences()->syncWithoutDetaching($documents->mapWithKeys(fn ($id) => [$id => ['linked_by' => $request->user()->id]])->all());
            }
        });

        return back()->with('status', 'Avancement de l’action enregistré.');
    }

    public function requestClosure(Request $request, ActionCorrective $action): RedirectResponse
    {
        $action = $this->visibleAction($request, $action);
        abort_unless($action->owner_user_id === $request->user()->id || $request->user()->isMissionOperationalContributor($action->auditRecommendation->mission), 403);
        abort_unless((int) $action->progress_percent === 100 && $action->evidences()->exists(), 422, 'Une action doit être achevée à 100 % et justifiée par une preuve.');
        $action->update(['statut' => 'closure_requested', 'closure_requested_at' => now(), 'completed_at' => now()]);
        $action->updates()->create(['user_id' => $request->user()->id, 'progress_percent' => 100, 'status' => 'closure_requested', 'comment' => $request->string('comment')->toString()]);

        return back()->with('status', 'Demande de clôture transmise au responsable de la mission.');
    }

    public function validateClosure(Request $request, ActionCorrective $action): RedirectResponse
    {
        $action = $this->visibleAction($request, $action);
        $mission = $action->auditRecommendation->mission;
        abort_unless($request->user()->canGovernMissionInstitutionally($mission), 403);
        abort_unless($action->statut === 'closure_requested', 422);
        $action->update(['statut' => 'ferme', 'progress_percent' => 100, 'closure_validated_by' => $request->user()->id, 'closure_validated_at' => now(), 'closure_comment' => $request->string('comment')->toString()]);
        $action->updates()->create(['user_id' => $request->user()->id, 'progress_percent' => 100, 'status' => 'ferme', 'comment' => $request->string('comment')->toString()]);

        return back()->with('status', 'Action corrective clôturée après validation humaine.');
    }

    private function visibleAction(Request $request, ActionCorrective $action): ActionCorrective
    {
        return ActionCorrective::query()->visibleToUser($request->user())->with(['auditRecommendation.mission', 'evidences'])->findOrFail($action->id);
    }
}
