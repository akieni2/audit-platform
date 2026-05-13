<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRisqueRequest;
use App\Http\Requests\UpdateRisqueRequest;
use App\Http\Resources\RiskResource;
use App\Models\Mission;
use App\Models\Risque;
use App\Services\Risk\EnterpriseHeatmapService;
use App\Services\Risk\MissionRiskDashboardService;
use App\Services\Risk\RiskRegistryPromotionService;
use App\Services\Risk\RiskRegistryQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RiskController extends Controller
{
    public function __construct(
        private RiskRegistryQueryService $riskRegistry,
        private MissionRiskDashboardService $dashboard,
        private EnterpriseHeatmapService $heatmaps,
        private RiskRegistryPromotionService $registry,
    ) {}

    public function indexForMission(Request $request, Mission $mission): AnonymousResourceCollection
    {
        $this->authorize('view', $mission);

        $risques = $this->riskRegistry->registry(['mission_id' => (int) $mission->id]);

        return RiskResource::collection($risques);
    }

    public function cartography(Request $request, Mission $mission): JsonResponse
    {
        $this->authorize('view', $mission);

        $missionId = (int) $mission->id;
        $snapshot = $this->dashboard->snapshot($missionId);
        $inherentHeatmap = $snapshot['heatmap']['combined'];
        $residualHeatmap = $snapshot['heatmap']['residual'];

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
                'critical_count' => $snapshot['critical_open'],
                'top_risques' => RiskResource::collection($this->riskRegistry->registry(['mission_id' => $missionId])->take(10))
                    ->toArray(request()),
                'monthly_creation' => $snapshot['monthly'],
                'by_department' => $snapshot['by_department'],
                'lifecycle' => $snapshot['lifecycle'],
                'criticality' => $snapshot['criticality'],
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

        $risque = $this->registry->ingestLegacySubmission($data, $request->user());

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
