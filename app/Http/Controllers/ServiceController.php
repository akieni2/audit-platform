<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesVisibleResources;
use App\Http\Requests\Services\StoreMissionServiceRequest;
use App\Http\Requests\Services\UpdateMissionServiceRequest;
use App\Models\Mission;
use App\Models\MissionService;
use App\Services\Iam\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    use ResolvesVisibleResources;

    public function index(Mission $mission): View
    {
        $this->authorize('view', $mission);

        $services = MissionService::query()
            ->where('mission_id', $mission->id)
            ->with([
                'chefServiceUser',
                'entretiens.questionnaireTemplate.sections.questions' => fn ($q) => $q->where('active', true),
                'entretiens.questionnaireResponses',
            ])
            ->withCount([
                'entretiens',
                'identifiedRisks',
                'missionDocuments',
            ])
            ->orderBy('id')
            ->get();

        return view('services.index', [
            'mission' => $mission,
            'services' => $services,
        ]);
    }

    public function store(StoreMissionServiceRequest $request, Mission $mission): RedirectResponse
    {
        $data = $request->validated();
        $data['mission_id'] = $mission->id;

        $service = MissionService::query()->create($data);

        app(SecurityAuditService::class)->missionServiceCreated($request->user(), $service, $request);

        return back()->with('status', 'Service audité ajouté.');
    }

    public function edit(Mission $mission, MissionService $service): View
    {
        abort_unless((int) $service->mission_id === (int) $mission->id, 404);
        $this->authorize('view', $mission);
        $this->authorize('update', $service);

        $actor = auth()->user();
        $eligibleChefUsers = collect();
        if ($actor !== null && $actor->can('manageServices', $mission)) {
            $eligibleChefUsers = $mission->eligibleTeamUsers($actor)->values();
        }

        return view('services.edit', [
            'mission' => $mission,
            'service' => $service,
            'eligibleChefUsers' => $eligibleChefUsers,
        ]);
    }

    public function update(UpdateMissionServiceRequest $request, Mission $mission, MissionService $service): RedirectResponse
    {
        abort_unless((int) $service->mission_id === (int) $mission->id, 404);

        $service->update($request->validated());

        app(SecurityAuditService::class)->missionServiceUpdated($request->user(), $service, $request);

        return redirect()
            ->route('services.index', $mission)
            ->with('status', 'Service mis à jour.');
    }

    public function destroy(Mission $mission, MissionService $service): RedirectResponse
    {
        abort_unless((int) $service->mission_id === (int) $mission->id, 404);
        $this->authorize('delete', $service);

        $service->delete();

        return back()->with('status', 'Service archivé.');
    }
}
