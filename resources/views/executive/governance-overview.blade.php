<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <header class="space-y-2">
            <p class="dgcpt-card-title">Gouvernance nationale</p>
            <h1 class="dgcpt-page-title">Vue d’ensemble de la gouvernance</h1>
            <p class="text-sm text-[#9FB3C8]">Vision consolidée des modèles globaux, hérités et gouvernés, ainsi que du niveau de standardisation institutionnelle.</p>
        </header>

        <div class="dgcpt-kpi-grid">
            @foreach ([
                'Missions nationales' => ($overview['national_missions'] ?? 0),
                'Paramètres départementaux' => ($overview['department_defaults'] ?? 0),
                'Workflows globaux' => ($overview['global_workflows'] ?? 0),
                'Workflows privés' => ($overview['private_workflows'] ?? 0),
                'Formulaires globaux' => ($overview['global_forms'] ?? 0),
                'Questionnaires globaux' => ($overview['global_questionnaires'] ?? 0),
            ] as $label => $value)
                <x-ui.kpi-card :label="$label" :value="$value" accent="cyan" />
            @endforeach
        </div>

        @if(!empty($overview['audit_follow_up']))
        <div class="dgcpt-kpi-grid">
            @foreach ([
                'Constats en brouillon' => $overview['audit_follow_up']['draft_findings'],
                'Contradictoire en cours' => $overview['audit_follow_up']['contradictory_findings'],
                'Recommandations validées' => $overview['audit_follow_up']['validated_recommendations'],
                'Actions en retard' => $overview['audit_follow_up']['overdue_actions'],
                'Clôtures à valider' => $overview['audit_follow_up']['closure_requests'],
            ] as $label => $value)
                <x-ui.kpi-card :label="$label" :value="$value" accent="cyan" />
            @endforeach
        </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="dgcpt-surface p-6">
                <p class="dgcpt-card-title">Cycle de vie</p>
                <h2 class="text-xl font-bold text-[#E6EEF8]">Répartition des statuts risques</h2>
                <div class="mt-4 space-y-2 text-sm text-[#BFD2E6]">
                    @foreach (data_get($overview, 'intelligence.lifecycle', []) as $status => $count)
                        <div class="flex items-center justify-between rounded-xl border border-[rgba(0,209,255,0.08)] px-3 py-2">
                            <span>{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $status)) }}</span>
                            <span class="font-semibold text-[#73D8FF]">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="dgcpt-surface p-6">
                <p class="dgcpt-card-title">Criticité</p>
                <h2 class="text-xl font-bold text-[#E6EEF8]">Répartition par criticité</h2>
                <div class="mt-4 space-y-2 text-sm text-[#BFD2E6]">
                    @foreach (data_get($overview, 'intelligence.criticality', []) as $level => $count)
                        <div class="flex items-center justify-between rounded-xl border border-[rgba(0,209,255,0.08)] px-3 py-2">
                            <span>{{ \Illuminate\Support\Str::headline($level) }}</span>
                            <span class="font-semibold text-[#73D8FF]">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
