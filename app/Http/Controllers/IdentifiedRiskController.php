<?php

namespace App\Http\Controllers;

use App\Models\IdentifiedRisk;
use App\Services\Iam\SecurityAuditService;
use App\Services\Risk\RiskRegistryPromotionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IdentifiedRiskController extends Controller
{
    public function __construct(
        private RiskRegistryPromotionService $registry,
    ) {}

    public function validateHuman(Request $request, IdentifiedRisk $identifiedRisk): RedirectResponse
    {
        $this->authorize('validateHuman', $identifiedRisk);

        try {
            $identifiedRisk = $this->registry->approve(
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

    public function submitForReview(Request $request, IdentifiedRisk $identifiedRisk): RedirectResponse
    {
        $this->authorize('validateHuman', $identifiedRisk);

        try {
            $identifiedRisk = $this->registry->submitForReview(
                $identifiedRisk,
                $request->user(),
                $request->string('comment')->toString(),
            );
        } catch (DomainException $exception) {
            return back()->with('status', $exception->getMessage());
        }

        return back()->with('status', 'Risque soumis au board de revue.');
    }

    public function approve(Request $request, IdentifiedRisk $identifiedRisk): RedirectResponse
    {
        return $this->validateHuman($request, $identifiedRisk);
    }

    public function reject(Request $request, IdentifiedRisk $identifiedRisk): RedirectResponse
    {
        $this->authorize('validateHuman', $identifiedRisk);

        try {
            $identifiedRisk = $this->registry->reject(
                $identifiedRisk,
                $request->user(),
                $request->string('comment')->toString(),
            );
        } catch (DomainException $exception) {
            return back()->with('status', $exception->getMessage());
        }

        return back()->with('status', 'Risque rejeté.');
    }

    public function promote(Request $request, IdentifiedRisk $identifiedRisk): RedirectResponse
    {
        $this->authorize('promote', $identifiedRisk);

        try {
            $risque = $this->registry->promote(
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
