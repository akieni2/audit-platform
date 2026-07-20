<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <header class="space-y-2">
            <p class="dgcpt-card-title">National Governance Layer</p>
            <h1 class="dgcpt-page-title">Governance Overview</h1>
            <p class="text-sm text-[#9FB3C8]">Vision consolidée des templates globaux, hérités, gouvernés et du niveau de standardisation enterprise.</p>
        </header>

        <div class="dgcpt-kpi-grid">
            @foreach ([
                'National missions' => ($overview['national_missions'] ?? 0),
                'Department defaults' => ($overview['department_defaults'] ?? 0),
                'Global workflows' => ($overview['global_workflows'] ?? 0),
                'Private workflows' => ($overview['private_workflows'] ?? 0),
                'Global forms' => ($overview['global_forms'] ?? 0),
                'Global questionnaires' => ($overview['global_questionnaires'] ?? 0),
            ] as $label => $value)
                <x-ui.kpi-card :label="$label" :value="$value" accent="cyan" />
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="dgcpt-surface p-6">
                <p class="dgcpt-card-title">Lifecycle</p>
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
