<?php

namespace App\Http\Controllers;

use App\Services\Governance\ExecutiveAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExecutiveAnalyticsController extends Controller
{
    public function __construct(
        private ExecutiveAnalyticsService $analytics,
    ) {}

    public function nationalDashboard(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        return view('executive.national-dashboard', [
            'snapshot' => $this->analytics->nationalSnapshot($actor),
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
}
