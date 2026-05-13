<?php

namespace App\Http\Controllers;

use App\Http\Requests\Services\StoreDepartmentAuditConsolidationRequest;
use App\Models\DepartmentAuditConsolidation;
use App\Models\Entretien;
use App\Models\IdentifiedRisk;
use App\Models\Mission;
use App\Models\MissionDocument;
use App\Services\Iam\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;

class DepartmentAuditConsolidationController extends Controller
{
    public function store(StoreDepartmentAuditConsolidationRequest $request, Mission $mission): RedirectResponse
    {
        $autoFindings = sprintf(
            "Services audités : %d\nEntretiens : %d\nRisques identifiés : %d\nDocuments : %d\n",
            $mission->services()->count(),
            Entretien::query()->where('mission_id', $mission->id)->count(),
            IdentifiedRisk::query()->where('mission_id', $mission->id)->count(),
            Schema::hasTable('mission_documents')
                ? MissionDocument::query()->where('mission_id', $mission->id)->count()
                : 0,
        );

        $row = DepartmentAuditConsolidation::query()->create([
            'mission_id' => $mission->id,
            'department_id' => $mission->department_id,
            'synthesis' => $request->input('synthesis'),
            'global_risk_level' => $request->input('global_risk_level'),
            'key_findings' => $request->input('key_findings') ?? $autoFindings,
            'recommendations' => $request->input('recommendations'),
            'generated_by_ai' => false,
            'validated_by' => null,
        ]);

        app(SecurityAuditService::class)->consolidationGenerated($request->user(), $row, $request);

        return back()->with('status', 'Consolidation départementale enregistrée (brouillon).');
    }
}
