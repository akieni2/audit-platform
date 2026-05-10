<?php

namespace App\Jobs;

use App\Models\Mission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Génération PDF asynchrone pour exploitation à forte charge (workers Horizon / Redis).
 */
class GenerateMissionPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    public int $tries = 3;

    public function __construct(
        public int $missionId,
    ) {}

    public function handle(): void
    {
        $mission = Mission::query()->find($this->missionId);
        if ($mission === null) {
            return;
        }

        $mission->load([
            'workflowEvents.user',
            'processus.actifs.risques.actionsCorrectives',
            'services',
            'department',
            'auditeur',
        ]);

        $pdf = Pdf::loadView('reports.mission', compact('mission'));

        Storage::disk('local')->makeDirectory('reports');
        $safeOrg = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string) $mission->organisation) ?: 'mission';

        Storage::disk('local')->put(
            'reports/mission_'.$mission->id.'_'.$safeOrg.'.pdf',
            $pdf->output()
        );
    }
}
