<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRisqueRequest;
use App\Http\Requests\UpdateRisqueRequest;
use App\Http\Resources\RiskResource;
use App\Models\Mission;
use App\Models\Risque;
use App\Repositories\Contracts\RiskRepositoryInterface;
use App\Services\Risk\HeatmapProjectionService;
use App\Services\Risk\RiskDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RiskController extends Controller
{
    public function __construct(
        private RiskRepositoryInterface $riskRepository,
        private RiskDashboardService $dashboard,
        private HeatmapProjectionService $heatmaps,
    ) {}

    public function indexForMission(Request $request, Mission $mission): AnonymousResourceCollection
    {
        $this->authorize('view', $mission);

        $risques = $this->riskRepository->forMission((int) $mission->id);

        return RiskResource::collection($risques);
    }

    public function cartography(Request $request, Mission $mission): JsonResponse
    {
        $this->authorize('view', $mission);

        $missionId = (int) $mission->id;
        $inherentHeatmap = $this->heatmaps->inherentForMission($missionId);
        $residualHeatmap = $this->heatmaps->residualForMission($missionId);

        $snapshot = $this->dashboard->snapshot($missionId);

        return response()->json([
            'mission_id' => $missionId,
            'heatmap' => array_map(
                fn (array $row) => array_map(
                    fn (array $cell) => [
                        'impact' => $cell['impact'],
                        'probabilite' => $cell['probabilite'],
                        'score_cellule' => $cell['score'],
                        'criticite' => $cell['criticite'],
                        'count' => $cell['count'],
                        'heatmap_color' => $cell['heatmap_color'],
                    ],
                    $row
                ),
                $inherentHeatmap['matrix']
            ),
            'heatmap_residual' => array_map(
                fn (array $row) => array_map(
                    fn (array $cell) => [
                        'impact' => $cell['impact'],
                        'probabilite' => $cell['probabilite'],
                        'score_cellule' => $cell['score'],
                        'criticite' => $cell['criticite'],
                        'count' => $cell['count'],
                        'heatmap_color' => $cell['heatmap_color'],
                    ],
                    $row
                ),
                $residualHeatmap['matrix']
            ),
            'dashboard' => [
                'critical_count' => $snapshot['critical_count'],
                'top_risques' => RiskResource::collection($snapshot['top_risks'])
                    ->toArray(request()),
                'monthly_creation' => $snapshot['monthly'],
                'by_department' => $snapshot['by_department'],
            ],
        ]);
    }

    public function show(Request $request, Risque $risque): RiskResource
    {
        $this->authorize('view', $risque);

        return new RiskResource($risque->load('controles'));
    }

    public function store(StoreRisqueRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['statut_risque'] = $data['statut_risque'] ?? 'identifie';

        $risque = Risque::create($data);
        $risque->calculerRisqueResiduel();

        return (new RiskResource($risque->fresh(['controles'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateRisqueRequest $request, Risque $risque): RiskResource
    {
        $risque->update($request->validated());
        $risque->calculerRisqueResiduel();

        return new RiskResource($risque->fresh(['controles']));
    }
}
