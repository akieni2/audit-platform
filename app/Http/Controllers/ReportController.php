<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mission;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function generate($id)
    {
        $mission = Mission::with('processus.actifs.risques.actionsCorrectives','services')->findOrFail($id);

        $pdf = Pdf::loadView('reports.mission', compact('mission'));

        return $pdf->download('rapport_audit_'.$mission->organisation.'.pdf');
    }
}
