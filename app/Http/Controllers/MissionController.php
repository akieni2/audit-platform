<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesVisibleResources;
use App\Http\Requests\Missions\StoreMissionRequest;
use App\Http\Requests\Missions\UpdateMissionRequest;
use App\Http\Requests\MissionWorkflowRequest;
use App\Models\Mission;
use App\Models\QuestionnaireTemplate;
use App\Services\Iam\SecurityAuditService;
use App\Services\Missions\MissionGovernanceService;
use App\Services\Missions\MissionWorkflowService;
use App\Services\Workflow\WorkflowCompatibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        return view('missions.create', ['creationToken' => (string) Str::uuid()]);
    }

    public function store(StoreMissionRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $creationToken = (string) ($request->validated('creation_token') ?: Str::uuid());
        $cacheKey = 'mission-creation:'.hash('sha256', $creationToken);

        if (! Cache::add($cacheKey, true, now()->addMinutes(15))) {
            return redirect()->route('missions.index')->with('status', 'Cette mission a déjà été enregistrée.');
        }

        try {
            $mission = DB::transaction(function () use ($request, $user): Mission {
                $mission = Mission::create([
                    ...$request->safe()->except('creation_token'),
                    'auditeur_id' => Auth::id(),
                    'department_id' => $user?->department_id,
                    'mission_status' => Mission::STATUS_BROUILLON,
                ]);

                app(WorkflowCompatibilityService::class)->ensureMissionWorkflow($mission, $request->user());
                app(SecurityAuditService::class)->missionCreated($request->user(), $mission, $request);

                return $mission;
            });
        } catch (\Throwable $exception) {
            Cache::forget($cacheKey);
            throw $exception;
        }

        return redirect()->route('missions.show', $mission)->with('status', 'Mission créée.');
    }

    public function destroy(Request $request, Mission $mission): RedirectResponse
    {
        $this->authorize('delete', $mission);

        app(SecurityAuditService::class)->missionDeleted($request->user(), $mission, $request);
        $mission->delete();

        return redirect()->route('missions.index')->with('status', 'Mission supprimée.');
    }

    public function show(Mission $mission): View
    {
        $this->authorize('view', $mission);

        $mission->load([
            'workflowEvents.user',
            'department.supervisor',
            'missionTeamMembers.user',
            'missionTeamMembers.assignedBy',
            'services.chefServiceUser',
            'auditGroups.questionnaireTemplate',
            'auditGroups.service',
            'auditGroups.members.user',
            'auditGroups.imports',
        ]);

        $governance = app(MissionGovernanceService::class);
        $workflow = app(WorkflowCompatibilityService::class);
        $actor = Auth::user();
        abort_unless($actor, 403);

        $eligibleTeamUsers = $governance->eligibleTeamUsers($actor, $mission);
        $missionStats = $governance->missionStats($mission);
        $missionProgressPercent = $governance->missionProgressPercent($missionStats);
        $workflowRuntime = $workflow->workflowViewData($mission, $actor);
        $questionnaireChoices = QuestionnaireTemplate::query()
            ->where('active', true)
            ->where(function ($query): void {
                $query->whereIn('lifecycle_status', [QuestionnaireTemplate::STATUS_PUBLISHED, QuestionnaireTemplate::STATUS_DRAFT])
                    ->orWhereNull('lifecycle_status');
            })
            ->with('methodologyTemplate')
            ->orderBy('name')
            ->get()
            ->filter(fn (QuestionnaireTemplate $template) => $template->isVisibleToDepartment($mission->department_id));

        return view('missions.show', [
            'mission' => $mission,
            'allowedActions' => $governance->allowedActions($actor, $mission),
            'eligibleTeamUsers' => $eligibleTeamUsers,
            'missionRoleLabels' => $governance->missionRoleLabels(),
            'missionStats' => $missionStats,
            'missionProgressPercent' => $missionProgressPercent,
            'workflowRuntime' => $workflowRuntime,
            'questionnaireChoices' => $questionnaireChoices,
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
        app(WorkflowCompatibilityService::class)->syncMissionWorkflow($fresh, $user);

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

        app(MissionGovernanceService::class)->transition(
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
