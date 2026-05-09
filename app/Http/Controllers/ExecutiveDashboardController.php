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
        return view('dashboard.executive', [
            'kpis' => $this->dashboard->nationalKpis(),
        ]);
    }
}
