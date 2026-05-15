<?php

namespace App\Http\Controllers;

use App\Domain\Ai\Enums\AiContextType;
use App\Domain\Ai\Enums\AiRecommendationType;
use App\Models\AiRecommendation;
use App\Models\Mission;
use App\Services\Ai\AiCopilotService;
use App\Services\Ai\Audit\AuditAiAssistantService;
use App\Services\Ai\Audit\AuditQuestionGeneratorService;
use App\Services\Ai\Control\InternalControlAiService;
use App\Services\Ai\Executive\ExecutiveAiAnalyticsService;
use App\Services\Ai\Knowledge\HistoricalLearningService;
use App\Services\Ai\Observability\AiMonitoringService;
use App\Services\Ai\Observability\AiPerformanceService;
use App\Services\Ai\Observability\AiUsageAnalyticsService;
use App\Services\Ai\Risk\RiskAiEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiCopilotController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user, 403);

        return view('ai.copilot', [
            'driver' => config('ai_copilot.default_driver'),
            'assistiveOnly' => true,
        ]);
    }

    public function copilotForMission(Request $request, Mission $mission): View
    {
        $this->authorize('view', $mission);

        return view('ai.copilot', [
            'mission' => $mission,
            'driver' => config('ai_copilot.default_driver'),
            'assistiveOnly' => true,
        ]);
    }

    public function assistant(Request $request, Mission $mission): View
    {
        $this->authorize('view', $mission);
        $user = $request->user();
        abort_unless($user, 403);

        $history = app(HistoricalLearningService::class)->pastRecommendations($mission);

        return view('ai.assistant', [
            'mission' => $mission,
            'history' => $history,
        ]);
    }

    public function recommendations(Request $request, ?Mission $mission = null): View
    {
        $user = $request->user();
        abort_unless($user, 403);

        $query = AiRecommendation::query()->latest('id')->with(['mission', 'user']);

        if ($mission !== null) {
            $this->authorize('view', $mission);
            $query->where('mission_id', $mission->id);
        } else {
            $missionIds = Mission::query()->visibleToUser($user)->pluck('id');
            $query->whereIn('mission_id', $missionIds);
        }

        return view('ai.recommendations', [
            'mission' => $mission,
            'recommendations' => $query->limit(50)->get(),
        ]);
    }

    public function analytics(Request $request): View
    {
        abort_unless($request->user()?->canAccessAdministrationMenu(), 403);

        return view('ai.analytics', [
            'monitoring' => app(AiMonitoringService::class)->snapshot(),
            'performance' => app(AiPerformanceService::class)->driversBreakdown(),
            'usage' => app(AiUsageAnalyticsService::class)->usage(),
        ]);
    }

    public function ask(Request $request, Mission $mission): RedirectResponse
    {
        $this->authorize('view', $mission);
        $user = $request->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:4000'],
            'context_type' => ['nullable', 'string'],
        ]);

        $contextType = AiContextType::tryFrom($validated['context_type'] ?? '') ?? AiContextType::Mission;

        app(AiCopilotService::class)->assist(
            $mission,
            $user,
            $contextType,
            $validated['prompt'],
        );

        return back()->with('status', 'Suggestion IA enregistrée — validation humaine requise.');
    }

    public function auditSummary(Request $request, Mission $mission): RedirectResponse
    {
        $this->authorize('view', $mission);
        app(AuditAiAssistantService::class)->missionSummary($mission, $request->user());

        return back()->with('status', 'Synthèse audit IA générée (assistive).');
    }

    public function auditQuestions(Request $request, Mission $mission): RedirectResponse
    {
        $this->authorize('view', $mission);
        $validated = $request->validate(['topic' => ['required', 'string', 'max:255']]);
        app(AuditQuestionGeneratorService::class)->generate($mission, $request->user(), $validated['topic']);

        return back()->with('status', 'Questions d\'audit suggérées par l\'IA.');
    }

    public function riskAnalysis(Request $request, Mission $mission): RedirectResponse
    {
        $this->authorize('view', $mission);
        app(RiskAiEngineService::class)->analyzeMission($mission, $request->user());

        return back()->with('status', 'Analyse risques IA enregistrée (assistive).');
    }

    public function controlAnalysis(Request $request, Mission $mission): RedirectResponse
    {
        $this->authorize('view', $mission);
        $framework = $request->string('framework', 'ISO27001')->toString();
        app(InternalControlAiService::class)->analyze($mission, $request->user(), $framework);

        return back()->with('status', 'Analyse contrôle interne IA enregistrée.');
    }

    public function acceptRecommendation(Request $request, AiRecommendation $recommendation): RedirectResponse
    {
        $mission = $recommendation->mission;
        abort_unless($mission, 404);
        $this->authorize('view', $mission);

        $recommendation->update([
            'accepted' => (bool) $request->boolean('accepted'),
            'accepted_at' => now(),
        ]);

        return back()->with('status', 'Recommandation IA marquée — décision humaine enregistrée.');
    }
}
