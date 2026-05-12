<?php

namespace App\Http\Controllers;

use App\Models\IdentifiedRisk;
use App\Services\Iam\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IdentifiedRiskController extends Controller
{
    public function validateHuman(Request $request, IdentifiedRisk $identifiedRisk): RedirectResponse
    {
        $this->authorize('validateHuman', $identifiedRisk);

        $identifiedRisk->update(['validated_by_human' => true]);

        app(SecurityAuditService::class)->riskValidated($request->user(), $identifiedRisk->fresh(), $request);

        return back()->with('status', 'Risque marqué comme validé humainement.');
    }
}
