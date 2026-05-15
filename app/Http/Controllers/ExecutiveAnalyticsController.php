<?php

namespace App\Http\Controllers;

use App\Services\Governance\EnterpriseRaciAnalyticsService;
use App\Services\Governance\EnterpriseSwotAnalyticsService;
use App\Services\Governance\ExecutiveAnalyticsService;
use App\Services\Governance\ExecutiveVisualizationService;
use App\Services\Governance\OrganizationalGapAnalysisService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExecutiveAnalyticsController extends Controller
{
    public function __construct(
        private ExecutiveAnalyticsService $analytics,
        private ExecutiveVisualizationService $visualization,
        private EnterpriseSwotAnalyticsService $swotAnalytics,
        private EnterpriseRaciAnalyticsService $raciAnalytics,
        private OrganizationalGapAnalysisService $gapAnalysis,
    ) {}

    public function nationalDashboard(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        $snapshot = $this->analytics->nationalSnapshot($actor);

        return view('executive.national-dashboard', [
            'snapshot' => $snapshot,
            'dashboardUx' => $this->visualization->nationalDashboard($snapshot),
        ]);
    }

    public function departmentComparison(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        return view('executive.department-comparison', [
            'comparison' => $this->analytics->departmentComparison($actor),
        ]);
    }

    public function riskIntelligence(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        return view('executive.risk-intelligence', [
            'intelligence' => $this->analytics->riskIntelligence($actor),
        ]);
    }

    public function maturityIndex(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        return view('executive.maturity-index', [
            'maturity' => $this->analytics->maturityIndex($actor),
        ]);
    }

    public function governanceOverview(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        return view('executive.governance-overview', [
            'overview' => $this->analytics->governanceOverview($actor),
        ]);
    }

    public function swotDashboard(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        return view('executive.swot-dashboard', [
            'dashboard' => $this->swotAnalytics->dashboard($actor),
        ]);
    }

    public function raciDashboard(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        return view('executive.raci-dashboard', [
            'dashboard' => $this->raciAnalytics->dashboard($actor),
        ]);
    }

    public function organizationalAnalysis(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        return view('executive.organizational-analysis', [
            'analysis' => $this->gapAnalysis->snapshot(
                $actor->canViewAllInstitutionalData() ? null : $actor->department_id
            ),
        ]);
    }
}
