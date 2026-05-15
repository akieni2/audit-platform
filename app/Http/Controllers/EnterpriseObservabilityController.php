<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\RuntimeSecurityEvent;
use App\Services\Audit\AuditIntegrityService;
use App\Services\Audit\RuntimeForensicsService;
use App\Services\Observability\AnalyticsMonitoringService;
use App\Services\Observability\EnterpriseHealthService;
use App\Services\Observability\ProjectionMonitoringService;
use App\Services\Observability\RuntimeDiagnosticsService;
use App\Services\Performance\QueryOptimizationService;
use App\Services\Runtime\QueueHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class EnterpriseObservabilityController extends Controller
{
    public function __construct(
        private EnterpriseHealthService $health,
        private RuntimeDiagnosticsService $diagnostics,
        private QueueHealthService $queues,
        private ProjectionMonitoringService $projections,
        private AnalyticsMonitoringService $analytics,
        private RuntimeForensicsService $forensics,
        private AuditIntegrityService $auditIntegrity,
        private QueryOptimizationService $queries,
    ) {}

    public function enterpriseHealth(Request $request): View
    {
        abort_unless($request->user()?->canAccessAdministrationMenu(), 403);

        return view('observability.enterprise-health', [
            'health' => $this->health->snapshot(),
        ]);
    }

    public function diagnostics(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        $mission = Mission::query()->visibleToUser($actor)->latest('id')->first();
        $diagnostics = $mission !== null
            ? $this->diagnostics->forMission($mission, $actor)
            : ['message' => 'Aucune mission visible pour diagnostic.'];

        return view('observability.diagnostics', [
            'diagnostics' => $diagnostics,
            'mission' => $mission,
        ]);
    }

    public function security(Request $request): View
    {
        abort_unless($request->user()?->canAccessSecurityLogs(), 403);

        $events = Schema::hasTable('runtime_security_events')
            ? RuntimeSecurityEvent::query()->latest('occurred_at')->limit(40)->with('actor')->get()
            : collect();

        return view('observability.security', [
            'events' => $events,
            'integrity' => $this->auditIntegrity->verifyChain(100),
            'forensics' => $this->forensics->findTamperGaps(),
        ]);
    }

    public function queues(Request $request): View
    {
        abort_unless($request->user(), 403);

        return view('observability.queue-monitoring', [
            'queueHealth' => $this->queues->snapshot(),
        ]);
    }

    public function performance(Request $request): View
    {
        abort_unless($request->user()?->canAccessAdministrationMenu(), 403);

        return view('observability.performance', [
            'analytics' => $this->analytics->snapshot(),
            'slowQueries' => $this->queries->slowQueries(),
        ]);
    }
}
