<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Repositories\Contracts\RiskRepositoryInterface;
use App\Services\Risk\CriticalityEvaluationService;
use App\Services\Risk\RiskDashboardService;
use Illuminate\View\View;

class CartographieController extends Controller
{
    public function __construct(
        private RiskRepositoryInterface $riskRepository,
        private RiskDashboardService $dashboard,
        private CriticalityEvaluationService $criticality,
    ) {}

    public function select(): View
    {
        $missions = Mission::orderByDesc('date_debut')->get();

        return view('cartographie.select', compact('missions'));
    }

    public function index(int $id): View
    {
        $mission = Mission::findOrFail($id);

        $risques = $this->riskRepository->forMission($id);
        $heatmapCounts = $this->riskRepository->inherentHeatmapCounts($id);

        $heatmapRows = [];
        for ($prob = 5; $prob >= 1; $prob--) {
            $row = [];
            for ($impact = 1; $impact <= 5; $impact++) {
                $score = $impact * $prob;
                $level = $this->criticality->levelFromScore($score);
                $key = $impact.'-'.$prob;
                $row[] = [
                    'impact' => $impact,
                    'probabilite' => $prob,
                    'score' => $score,
                    'level' => $level,
                    'cell_classes' => $this->criticality->heatmapCellClasses($level),
                    'count' => $heatmapCounts[$key] ?? 0,
                ];
            }
            $heatmapRows[] = $row;
        }

        $snapshot = $this->dashboard->snapshot($id);

        return view('cartographie.index', [
            'mission' => $mission,
            'risques' => $risques,
            'heatmapRows' => $heatmapRows,
            'dashboard' => $snapshot,
        ]);
    }
}
