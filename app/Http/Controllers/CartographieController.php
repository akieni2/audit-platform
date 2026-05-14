<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Services\Risk\EnterpriseHeatmapService;
use App\Services\Risk\HeatmapVisualizationService;
use App\Services\Risk\MissionRiskDashboardService;
use App\Services\Risk\RiskRegistryQueryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CartographieController extends Controller
{
    public function __construct(
        private RiskRegistryQueryService $riskRegistry,
        private MissionRiskDashboardService $dashboard,
        private EnterpriseHeatmapService $heatmaps,
        private HeatmapVisualizationService $visualization,
    ) {}

    public function select(): View
    {
        $user = Auth::user();
        $missions = Mission::query()
            ->when($user, fn ($q) => $q->visibleToUser($user))
            ->orderByDesc('date_debut')
            ->get();

        return view('cartographie.select', compact('missions'));
    }

    public function index(Mission $mission): View
    {
        $this->authorize('view', $mission);

        $id = $mission->id;

        $snapshot = $this->dashboard->snapshot($id);
        $heatmap = $snapshot['heatmap']['combined'];
        $residualHeatmap = $snapshot['heatmap']['residual'];
        $officialRisks = $this->riskRegistry->registry(['mission_id' => $id]);

        return view('cartographie.index', [
            'mission' => $mission,
            'risques' => $officialRisks,
            'heatmapRows' => $heatmap['matrix'],
            'residualHeatmapRows' => $residualHeatmap['matrix'],
            'dashboard' => [
                ...$snapshot,
                'critical_count' => $snapshot['critical_open'],
                'top_risks' => $officialRisks->take(10),
            ],
            'heatmapView' => $this->visualization->build(
                mission: $mission,
                heatmapRows: $heatmap['matrix'],
                residualHeatmapRows: $residualHeatmap['matrix'],
                dashboard: [
                    ...$snapshot,
                    'critical_count' => $snapshot['critical_open'],
                    'top_risks' => $officialRisks->take(10),
                ],
                risks: $officialRisks,
            ),
        ]);
    }
}
