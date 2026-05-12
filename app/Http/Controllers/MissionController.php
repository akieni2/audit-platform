<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesVisibleResources;
use App\Http\Requests\Missions\StoreMissionRequest;
use App\Http\Requests\Missions\UpdateMissionRequest;
use App\Http\Requests\MissionWorkflowRequest;
use App\Models\Entretien;
use App\Models\IdentifiedRisk;
use App\Models\Mission;
use App\Models\MissionDocument;
use App\Models\MissionTeamMember;
use App\Services\Iam\SecurityAuditService;
use App\Services\Missions\MissionWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MissionController extends Controller
{
    use ResolvesVisibleResources;

    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Mission::query()
            ->when($user, fn ($q) => $q->visibleToUser($user))
            ->with('department');

        if ($request->filled('department')) {
            $query->where('department_id', (int) $request->query('department'));
        }

        if ($request->filled('status')) {
            $query->where('mission_status', (string) $request->query('status'));
        }

        $qTerm = trim((string) $request->query('q', ''));
        if ($qTerm !== '') {
            $query->where(function ($q) use ($qTerm) {
                $like = '%'.$qTerm.'%';
                $q->where('organisation', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('reference', 'like', $like)
                    ->orWhere('objet', 'like', $like);
            });
        }

        $missions = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('missions.index', compact('missions'));
    }

    public function create(): View
    {
        $this->authorize('create', Mission::class);

        return view('missions.create');
    }

    public function store(StoreMissionRequest $request): RedirectResponse
    {
        $user = Auth::user();

        $mission = Mission::create([
            ...$request->validated(),
            'auditeur_id' => Auth::id(),
            'department_id' => $user?->department_id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        app(SecurityAuditService::class)->missionCreated($request->user(), $mission, $request);

        return redirect()->route('missions.show', $mission)->with('status', 'Mission créée.');
    }

    public function show(Mission $mission): View
    {
        $this->authorize('view', $mission);

        $mission->load([
            'workflowEvents.user',
            'department.supervisor',
            'missionTeamMembers.user',
            'missionTeamMembers.assignedBy',
        ]);

        $workflow = app(MissionWorkflowService::class);
        $actor = Auth::user();
        abort_unless($actor, 403);

        $eligibleTeamUsers = collect();
        if ($actor->can('assignTeamMembers', $mission)) {
            $existingIds = $mission->missionTeamMembers->pluck('user_id');
            $eligibleTeamUsers = $mission->eligibleTeamUsers($actor)
                ->whereNotIn('id', $existingIds)
                ->values();
        }

        $missionStats = [
            'services_count' => $mission->services()->where('active', true)->count(),
            'entretiens_total' => Entretien::query()->where('mission_id', $mission->id)->count(),
            'entretiens_done' => Entretien::query()
                ->where('mission_id', $mission->id)
                ->whereIn('status', [Entretien::STATUS_COMPLETED, Entretien::STATUS_VALIDATED])
                ->count(),
            'risks_count' => IdentifiedRisk::query()->where('mission_id', $mission->id)->count(),
            'risks_critical' => IdentifiedRisk::query()
                ->where('mission_id', $mission->id)
                ->whereIn('criticality', ['Critique', 'critique', 'High', 'high', 'Élevée', 'élevée', 'Elevée', 'elevée'])
                ->count(),
            'documents_count' => MissionDocument::query()->where('mission_id', $mission->id)->count(),
        ];
        $missionProgressPercent = $missionStats['entretiens_total'] > 0
            ? (int) min(100, max(0, (int) round(100 * $missionStats['entretiens_done'] / $missionStats['entretiens_total'])))
            : null;

        return view('missions.show', [
            'mission' => $mission,
            'allowedActions' => $workflow->allowedActions($actor, $mission),
            'eligibleTeamUsers' => $eligibleTeamUsers,
            'missionRoleLabels' => MissionTeamMember::missionRoleLabels(),
            'missionStats' => $missionStats,
            'missionProgressPercent' => $missionProgressPercent,
        ]);
    }

    public function edit(Mission $mission): View
    {
        $this->authorize('editMission', $mission);

        return view('missions.edit', compact('mission'));
    }

    public function update(UpdateMissionRequest $request, Mission $mission): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        $validated = $request->validated();
        $audit = app(SecurityAuditService::class);

        $mission->update($validated);
        $fresh = $mission->fresh();

        if ($user->can('governMission', $fresh)) {
            $deadlineFields = array_values(array_intersect(array_keys($validated), UpdateMissionRequest::deadlineKeys()));
            if ($deadlineFields !== []) {
                $audit->missionDeadlinesUpdated($user, $fresh, $request, $deadlineFields);
            }

            $otherFields = array_values(array_diff(array_keys($validated), UpdateMissionRequest::deadlineKeys()));
            if ($otherFields !== []) {
                $audit->missionOrdreUpdated($user, $fresh, $request, $otherFields);
            }
        } elseif ($user->can('updateMissionContent', $fresh)) {
            $audit->missionOperationalContentUpdated($user, $fresh, $request, array_keys($validated));
        }

        return redirect()->route('missions.show', $mission)->with('status', 'Mission mise à jour.');
    }

    public function workflow(MissionWorkflowRequest $request, Mission $mission): RedirectResponse
    {
        $action = $request->validated('action');
        $this->authorize('transition', [$mission, $action]);

        app(MissionWorkflowService::class)->transition(
            Auth::user(),
            $mission,
            $action,
            $request->input('comment')
        );

        if ($action === MissionWorkflowService::ACTION_CLOTURER) {
            app(SecurityAuditService::class)->missionClosed(
                $request->user(),
                $mission->fresh(),
                $request
            );
        }

        return redirect()->route('missions.show', $mission)->with('status', 'Décision enregistrée.');
    }
}
