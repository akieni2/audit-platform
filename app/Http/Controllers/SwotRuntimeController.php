<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\SwotTemplate;
use App\Services\Swot\SwotAnalyticsService;
use App\Services\Swot\SwotConsolidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SwotRuntimeController extends Controller
{
    public function __construct(
        private SwotAnalyticsService $analytics,
        private SwotConsolidationService $consolidation,
    ) {}

    public function show(Request $request, Mission $mission): View
    {
        $this->authorize('view', $mission);

        return view('swot.runtime.show', [
            'mission' => $mission,
            'swotView' => $this->analytics->missionSnapshot($mission),
            'swotTemplates' => SwotTemplate::query()
                ->where(function ($query) use ($mission) {
                    $query->whereNull('department_id')
                        ->orWhere('department_id', $mission->department_id)
                        ->orWhere('is_global', true);
                })
                ->where('active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function analyze(Request $request, Mission $mission): RedirectResponse
    {
        $this->authorize('view', $mission);

        $validated = $request->validate([
            'swot_template_id' => ['required', 'exists:swot_templates,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $template = SwotTemplate::query()->findOrFail($validated['swot_template_id']);
        $this->analytics->runMissionAnalysis($template, $mission, [
            'actor_id' => $request->user()?->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('swot.show', $mission)->with('status', 'Analyse SWOT executee.');
    }

    public function recommendations(Request $request, Mission $mission): View
    {
        $this->authorize('view', $mission);

        return view('swot.runtime.recommendations-page', [
            'mission' => $mission,
            'swotView' => $this->analytics->missionSnapshot($mission),
        ]);
    }

    public function consolidation(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        return view('swot.runtime.consolidation', [
            'consolidation' => $this->consolidation->snapshot(
                $actor->canViewAllInstitutionalData() ? null : $actor->department_id
            ),
        ]);
    }
}
