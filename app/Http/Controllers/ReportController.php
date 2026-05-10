<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateMissionPdfJob;
use App\Models\Mission;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function generate(Mission $mission)
    {
        $this->authorize('view', $mission);

        if (config('audit.queue_mission_pdf')) {
            GenerateMissionPdfJob::dispatch($mission->id);

            return redirect()
                ->route('missions.show', $mission)
                ->with('status', 'Le rapport PDF est mis en file d\'attente de génération (workers). Le fichier sera disponible sur le stockage applicatif.');
        }

        $mission->load([
            'workflowEvents.user',
            'processus.actifs.risques.actionsCorrectives',
            'services',
            'department',
            'auditeur',
        ]);

        $pdf = Pdf::loadView('reports.mission', compact('mission'));

        return $pdf->download('rapport_audit_'.$mission->organisation.'.pdf');
    }
}

