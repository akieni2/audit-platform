<?php

namespace App\Http\Controllers;

use App\Services\Governance\ExecutiveDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExecutiveDashboardController extends Controller
{
    public function __construct(
        private ExecutiveDashboardService $dashboard,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user, 403);

        $user->loadMissing('institutionalRole');
        $slug = $user->institutionalRole?->slug;

        $kpis = $this->dashboard->nationalKpis();

        if ($slug === 'copri') {
            return view('dashboard.copri', compact('kpis'));
        }

        if (in_array($slug, ['inspecteur_services', 'inspecteur_adjoint'], true)
            || $user->hasPermission('supervise_global')) {
            return view('dashboard.inspection', [
                'kpis' => $kpis,
                'awaitingIs' => $this->dashboard->missionsAwaitingInspection(),
                'awaitingCopri' => $this->dashboard->missionsAwaitingCopri(),
            ]);
        }

        return view('dashboard.executive', compact('kpis'));
    }
}
