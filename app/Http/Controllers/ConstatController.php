<?php

namespace App\Http\Controllers;

use App\Models\AuditRecommendation;
use App\Models\Constat;
use App\Models\EntretienResponse;
use App\Models\Mission;
use App\Models\User;
use App\Services\Audit\ConstatWorkflowService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConstatController extends Controller
{
    public function __construct(private ConstatWorkflowService $workflow) {}

    public function index(Mission $mission): View
    {
        $this->authorize('view', $mission);
        $mission->load(['constats.creator', 'constats.evidences', 'services']);

        return view('constats.index', compact('mission'));
    }

    public function create(Mission $mission): View
    {
        $this->authorize('updateMissionContent', $mission);
        $mission->load(['services', 'missionDocuments']);
        $responses = EntretienResponse::query()
            ->whereHas('entretien', fn ($q) => $q->where('mission_id', $mission->id))
            ->with('question')->latest()->get();

        return view('constats.create', compact('mission', 'responses'));
    }

    public function store(Request $request, Mission $mission): RedirectResponse
    {
        $this->authorize('updateMissionContent', $mission);
        $data = $this->validateFinding($request, $mission);

        $constat = DB::transaction(function () use ($data, $mission, $request): Constat {
            $evidenceIds = $data['evidence_ids'] ?? [];
            unset($data['evidence_ids']);
            $data['mission_id'] = $mission->id;
            $data['description'] = $data['condition_observed'];
            $data['created_by'] = $request->user()->id;
            $constat = Constat::query()->create($data);
            $constat->update(['reference' => sprintf('CST-%s-%04d', $mission->id, $constat->id)]);
            $this->syncEvidences($constat, $evidenceIds, $request->user()->id);

            return $constat;
        });

        return redirect()->route('constats.show', $constat)->with('status', 'Constat créé en brouillon.');
    }

    public function show(Constat $constat): View
    {
        $constat = $this->visible($constat);
        $constat->load(['mission.department', 'service', 'question', 'response', 'creator', 'reviewer', 'validator', 'evidences', 'reviews.user', 'auditeeResponses.respondent', 'recommendations.identifiedRisk.promotedRisk', 'recommendations.actions.updates.user']);
        $constat->mission->load(['missionDocuments', 'missionTeamMembers.user']);
        $users = User::query()->where('active', true)->orderBy('name')->get();

        return view('constats.show', compact('constat', 'users'));
    }

    public function update(Request $request, Constat $constat): RedirectResponse
    {
        $constat = $this->visible($constat);
        $this->authorize('updateMissionContent', $constat->mission);
        abort_unless(in_array($constat->status, [Constat::STATUS_DRAFT, Constat::STATUS_TEAM_REVIEW], true), 422);
        $data = $this->validateFinding($request, $constat->mission);
        $evidenceIds = $data['evidence_ids'] ?? [];
        unset($data['evidence_ids']);
        $data['description'] = $data['condition_observed'];
        DB::transaction(function () use ($constat, $data, $evidenceIds, $request): void {
            $constat->update($data + ['version' => $constat->version + 1]);
            $this->syncEvidences($constat, $evidenceIds, $request->user()->id);
        });

        return back()->with('status', 'Constat mis à jour.');
    }

    public function transition(Request $request, Constat $constat, string $action): RedirectResponse
    {
        $constat = $this->visible($constat);
        if (in_array($action, ['validate_mission', 'open_contradictory', 'finalize'], true)) {
            $this->authorize('governMission', $constat->mission);
        } else {
            $this->authorize('updateMissionContent', $constat->mission);
        }
        try {
            $this->workflow->transition($constat, $request->user(), $action, $request->string('comment')->toString());
        } catch (DomainException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('status', 'Circuit du constat mis à jour.');
    }

    public function review(Request $request, Constat $constat): RedirectResponse
    {
        $constat = $this->visible($constat);
        $this->authorize('updateMissionContent', $constat->mission);
        $data = $request->validate(['decision' => ['required', Rule::in(['approved', 'changes_requested'])], 'comment' => ['nullable', 'string', 'max:5000']]);
        try {
            $this->workflow->recordTeamReview($constat, $request->user(), $data['decision'], $data['comment'] ?? null);
        } catch (DomainException $e) {
            return back()->withErrors(['review' => $e->getMessage()]);
        }

        return back()->with('status', 'Avis de revue enregistré.');
    }

    public function auditeeResponse(Request $request, Constat $constat): RedirectResponse
    {
        $constat = $this->visible($constat);
        abort_unless($constat->status === Constat::STATUS_CONTRADICTORY, 422);
        $data = $request->validate([
            'position' => ['required', Rule::in(['accepted', 'partially_accepted', 'contested'])],
            'observation' => ['required', 'string', 'max:20000'],
            'proposed_action' => ['nullable', 'string', 'max:20000'],
            'proposed_owner_user_id' => ['nullable', 'exists:users,id'],
            'proposed_due_date' => ['nullable', 'date'],
        ]);
        $constat->auditeeResponses()->create($data + [
            'responded_by' => $request->user()->id,
            'department_id' => $request->user()->department_id,
            'responded_at' => now(),
        ]);

        return back()->with('status', 'Réponse contradictoire enregistrée.');
    }

    public function convertToRisk(Request $request, Constat $constat): RedirectResponse
    {
        $constat = $this->visible($constat);
        $this->authorize('governMission', $constat->mission);
        try {
            $risk = $this->workflow->convertToRisk($constat, $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['risk' => $e->getMessage()]);
        }

        return back()->with('status', 'Risque détecté créé : '.$risk->title);
    }

    public function validateRecommendation(Request $request, AuditRecommendation $recommendation): RedirectResponse
    {
        $recommendation = AuditRecommendation::query()->visibleToUser($request->user())->findOrFail($recommendation->id);
        $this->authorize('governMission', $recommendation->mission);
        $data = $request->validate(['owner_user_id' => ['nullable', 'exists:users,id'], 'owner_department_id' => ['nullable', 'exists:departments,id'], 'due_date' => ['nullable', 'date']]);
        $recommendation->update($data + ['status' => 'validated', 'validated_by' => $request->user()->id, 'validated_at' => now()]);

        return back()->with('status', 'Recommandation institutionnelle validée.');
    }

    public function storeAction(Request $request, AuditRecommendation $recommendation): RedirectResponse
    {
        $recommendation = AuditRecommendation::query()->visibleToUser($request->user())->findOrFail($recommendation->id);
        $this->authorize('governMission', $recommendation->mission);
        abort_unless($recommendation->status === 'validated', 422);
        $data = $request->validate(['description' => ['required', 'string', 'max:20000'], 'responsable' => ['nullable', 'string', 'max:255'], 'owner_user_id' => ['nullable', 'exists:users,id'], 'owner_department_id' => ['nullable', 'exists:departments,id'], 'date_echeance' => ['nullable', 'date']]);
        $this->workflow->createAction($recommendation, $data, $request->user());

        return back()->with('status', 'Action corrective ajoutée.');
    }

    private function visible(Constat $constat): Constat
    {
        return Constat::query()->visibleToUser(request()->user())->with('mission')->findOrFail($constat->id);
    }

    private function validateFinding(Request $request, Mission $mission): array
    {
        $data = $request->validate([
            'service_id' => ['nullable', Rule::exists('services', 'id')->where('mission_id', $mission->id)],
            'entretien_response_id' => ['nullable', Rule::exists('entretien_responses', 'id')],
            'questionnaire_question_id' => ['nullable', Rule::exists('questionnaire_questions', 'id')],
            'criterion' => ['required', 'string', 'max:10000'],
            'condition_observed' => ['required', 'string', 'max:20000'],
            'cause' => ['nullable', 'string', 'max:20000'],
            'consequence' => ['nullable', 'string', 'max:20000'],
            'gravite' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'recommandation' => ['required', 'string', 'max:20000'],
            'evidence_ids' => ['nullable', 'array'],
            'evidence_ids.*' => [Rule::exists('mission_documents', 'id')->where('mission_id', $mission->id)],
        ]);

        if (! empty($data['entretien_response_id'])) {
            $response = EntretienResponse::query()
                ->whereKey($data['entretien_response_id'])
                ->whereHas('entretien', fn ($query) => $query->where('mission_id', $mission->id))
                ->firstOrFail();
            $data['questionnaire_question_id'] = $response->questionnaire_question_id;
        }

        return $data;
    }

    private function syncEvidences(Constat $constat, array $ids, int $userId): void
    {
        $constat->evidences()->sync(collect($ids)->mapWithKeys(fn ($id) => [(int) $id => ['linked_by' => $userId]])->all());
    }
}
