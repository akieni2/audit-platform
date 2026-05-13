<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Repositories\Contracts\RiskRepositoryInterface;
use App\Services\Risk\HeatmapProjectionService;
use App\Services\Risk\RiskDashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CartographieController extends Controller
{
    public function __construct(
        private RiskRepositoryInterface $riskRepository,
        private RiskDashboardService $dashboard,
        private HeatmapProjectionService $heatmaps,
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

        $risques = $this->riskRepository->forMission($id);
        $heatmap = $this->heatmaps->inherentForMission($id);
        $residualHeatmap = $this->heatmaps->residualForMission($id);

        $snapshot = $this->dashboard->snapshot($id);

        return view('cartographie.index', [
            'mission' => $mission,
            'risques' => $risques,
            'heatmapRows' => $heatmap['matrix'],
            'residualHeatmapRows' => $residualHeatmap['matrix'],
            'dashboard' => $snapshot,
        ]);
    }
}
