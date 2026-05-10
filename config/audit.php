<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PDF export — file d’attente (production)
    |--------------------------------------------------------------------------
    |
    | Si true, la génération est déléguée à GenerateMissionPdfJob (workers).
    | Sinon téléchargement synchrone (comportement historique).
    |
    */

    'queue_mission_pdf' => env('QUEUE_MISSION_PDF', false),

    /*
    |--------------------------------------------------------------------------
    | Cache KPI nationaux (ExecutiveDashboardService)
    |--------------------------------------------------------------------------
    */

    'kpi_cache_ttl' => (int) env('EXEC_KPI_CACHE_TTL', 120),

];
