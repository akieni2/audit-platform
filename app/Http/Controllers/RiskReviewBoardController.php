<?php

namespace App\Http\Controllers;

use App\Domain\Risk\Enums\RiskLifecycleStatus;
use App\Services\Risk\NationalRiskDashboardService;
use App\Services\Risk\RiskRegistryQueryService;
use Illuminate\View\View;

class RiskReviewBoardController extends Controller
{
    public function __construct(
        private RiskRegistryQueryService $registry,
        private NationalRiskDashboardService $dashboard,
    ) {}

    public function index(): View
    {
        $intakeQueue = $this->registry->intakeQuery()
            ->whereIn('lifecycle_status', [
                RiskLifecycleStatus::Detected->value,
                RiskLifecycleStatus::UnderReview->value,
                RiskLifecycleStatus::Validated->value,
            ])
            ->with(['mission.department', 'service', 'creator', 'reviewer', 'approver'])
            ->orderByDesc('updated_at')
            ->get();

        $officialRisks = $this->registry->officialQuery()
            ->with(['ownerDepartment', 'owner', 'sourceIdentifiedRisk'])
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return view('risks.review-board', [
            'intakeQueue' => $intakeQueue,
            'officialRisks' => $officialRisks,
            'dashboard' => $this->dashboard->snapshot(),
            'lifecycleLabels' => RiskLifecycleStatus::labels(),
            'lifecycleColors' => RiskLifecycleStatus::colors(),
        ]);
    }
}
