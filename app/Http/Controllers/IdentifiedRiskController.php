<?php

namespace App\Http\Controllers;

use App\Models\IdentifiedRisk;
use App\Services\Iam\SecurityAuditService;
use App\Services\Risk\RiskPromotionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IdentifiedRiskController extends Controller
{
    public function validateHuman(Request $request, IdentifiedRisk $identifiedRisk): RedirectResponse
    {
        $this->authorize('validateHuman', $identifiedRisk);

        try {
            $identifiedRisk = app(RiskPromotionService::class)->markReviewed(
                $identifiedRisk,
                $request->user(),
                $request->string('comment')->toString(),
            );
        } catch (DomainException $exception) {
            return back()->with('status', $exception->getMessage());
        }

        app(SecurityAuditService::class)->riskValidated($request->user(), $identifiedRisk->fresh(), $request);

        return back()->with('status', 'Risque marqué comme validé humainement.');
    }

    public function promote(Request $request, IdentifiedRisk $identifiedRisk): RedirectResponse
    {
        $this->authorize('promote', $identifiedRisk);

        try {
            $risque = app(RiskPromotionService::class)->promote(
                $identifiedRisk,
                $request->user(),
                $request->string('comment')->toString(),
            );
        } catch (DomainException $exception) {
            return back()->with('status', $exception->getMessage());
        }

        app(SecurityAuditService::class)->riskPromoted(
            $request->user(),
            $identifiedRisk->fresh(),
            $risque,
            $request,
        );

        return back()->with('status', 'Risque promu vers le registre officiel #'.$risque->id.'.');
    }
}
