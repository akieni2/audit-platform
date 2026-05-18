<?php

namespace App\Http\Controllers\Dgcpt;

use App\Http\Controllers\Controller;
use App\Models\Dgcpt\TreasuryEntity;
use App\Services\Dgcpt\DgcptHierarchyService;
use App\Services\Dgcpt\DgcptNationalConsolidationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DgcptHierarchyController extends Controller
{
    public function __construct(
        private DgcptHierarchyService $hierarchy,
        private DgcptNationalConsolidationService $consolidation,
    ) {}

    public function index(): View
    {
        $user = Auth::user();
        $this->authorize('viewAny', TreasuryEntity::class);

        return view('dgcpt.hierarchy.index', [
            'tree' => $this->hierarchy->tree($user),
            'domains' => $this->hierarchy->activeDomains(),
            'templates' => $this->hierarchy->activeTemplates(),
        ]);
    }

    public function national(): View
    {
        $this->authorize('viewAny', TreasuryEntity::class);

        return view('dgcpt.consolidation.national', [
            'snapshot' => $this->consolidation->nationalSnapshot(),
        ]);
    }

    public function province(TreasuryEntity $treasuryEntity): View
    {
        $this->authorize('view', $treasuryEntity);

        return view('dgcpt.consolidation.province', [
            'snapshot' => $this->consolidation->provinceSnapshot($treasuryEntity),
            'entity' => $treasuryEntity,
        ]);
    }
}
