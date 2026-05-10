<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function generate(Mission $mission)
    {
        $this->authorize('view', $mission);

        $mission->load(['processus.actifs.risques.actionsCorrectives', 'services']);

        $pdf = Pdf::loadView('reports.mission', compact('mission'));

        return $pdf->download('rapport_audit_'.$mission->organisation.'.pdf');
    }
}
