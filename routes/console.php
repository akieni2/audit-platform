<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('platform:integrity-audit', function () {
    $checks = [
        ['services', 'mission_id', 'missions', 'id'],
        ['entretiens', 'mission_id', 'missions', 'id'],
        ['entretiens', 'service_id', 'services', 'id'],
        ['processus', 'mission_id', 'missions', 'id'],
        ['actifs', 'processus_id', 'processus', 'id'],
        ['risques', 'actif_id', 'actifs', 'id'],
        ['controles', 'risque_id', 'risques', 'id'],
        ['actions_correctives', 'risque_id', 'risques', 'id'],
        ['questionnaires', 'entretien_id', 'entretiens', 'id'],
        ['questions', 'questionnaire_id', 'questionnaires', 'id'],
        ['reponses', 'entretien_id', 'entretiens', 'id'],
        ['reponses', 'question_id', 'questions', 'id'],
        ['audit_plans', 'mission_id', 'missions', 'id'],
        ['audit_programmes', 'audit_plan_id', 'audit_plans', 'id'],
    ];

    $rows = collect($checks)->map(function (array $check) {
        [$fromTable, $fromColumn, $toTable, $toColumn] = $check;

        if (! Schema::hasTable($fromTable) || ! Schema::hasTable($toTable)) {
            return [
                'from' => "{$fromTable}.{$fromColumn}",
                'to' => "{$toTable}.{$toColumn}",
                'orphans' => 'n/a',
            ];
        }

        $orphans = DB::table($fromTable)
            ->leftJoin($toTable, "{$fromTable}.{$fromColumn}", '=', "{$toTable}.{$toColumn}")
            ->whereNotNull("{$fromTable}.{$fromColumn}")
            ->whereNull("{$toTable}.{$toColumn}")
            ->count();

        return [
            'from' => "{$fromTable}.{$fromColumn}",
            'to' => "{$toTable}.{$toColumn}",
            'orphans' => $orphans,
        ];
    });

    $this->info('Audit des relations legacy/non contraintes');
    $this->table(['Référence source', 'Référence cible', 'Orphelins'], $rows->all());

    $missionDraftCount = Schema::hasTable('missions')
        ? DB::table('missions')->where('mission_status', 'draft')->count()
        : 0;
    $legacyCriticalityCount = Schema::hasTable('identified_risks')
        ? DB::table('identified_risks')
            ->whereNotNull('criticality')
            ->whereNotIn('criticality', ['low', 'medium', 'high', 'critical'])
            ->count()
        : 0;

    $this->newLine();
    $this->line("missions.mission_status = 'draft' : {$missionDraftCount}");
    $this->line("identified_risks.criticality hors référentiel canonique : {$legacyCriticalityCount}");
})->purpose('Audit orphan rows and status drift before structural FKs');
