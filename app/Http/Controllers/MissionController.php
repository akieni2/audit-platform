<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesVisibleResources;
use App\Http\Requests\MissionWorkflowRequest;
use App\Models\Mission;
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

        $missions = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('missions.index', compact('missions'));
    }

    public function create(): View
    {
        $this->authorize('create', Mission::class);

        return view('missions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Mission::class);

        $user = Auth::user();

        $mission = Mission::create([
            'organisation' => $request->organisation,
            'description' => $request->description,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'auditeur_id' => Auth::id(),
            'department_id' => $user?->department_id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        return redirect()->route('missions.show', $mission)->with('status', 'Mission créée.');
    }

    public function show(Mission $mission): View
    {
        $this->authorize('view', $mission);

        $mission->load(['workflowEvents.user', 'department']);

        $workflow = app(MissionWorkflowService::class);
        $actor = Auth::user();
        abort_unless($actor, 403);

        return view('missions.show', [
            'mission' => $mission,
            'allowedActions' => $workflow->allowedActions($actor, $mission),
        ]);
    }

    public function edit(Mission $mission): View
    {
        $this->authorize('update', $mission);

        return view('missions.edit', compact('mission'));
    }

    public function update(Request $request, Mission $mission): RedirectResponse
    {
        $this->authorize('update', $mission);

        $mission->update([
            'organisation' => $request->organisation,
            'description' => $request->description,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
        ]);

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

        return redirect()->route('missions.show', $mission)->with('status', 'Décision enregistrée.');
    }
}
