<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <header class="space-y-2">
            <p class="dgcpt-card-title">Executive Analytics Platform</p>
            <h1 class="dgcpt-page-title">National Dashboard</h1>
            <p class="text-sm text-[#9FB3C8]">Vue consolidée nationale: gouvernance, intelligence risque, méthodologies, taxonomies et performance workflow.</p>
        </header>

        <div class="dgcpt-kpi-grid">
            @foreach (($snapshot['executive_kpis'] ?? []) as $label => $value)
                <x-ui.kpi-card :label="\Illuminate\Support\Str::headline(str_replace('_', ' ', $label))" :value="$value" />
            @endforeach
            @foreach (($snapshot['governance'] ?? []) as $label => $value)
                <x-ui.kpi-card :label="\Illuminate\Support\Str::headline(str_replace('_', ' ', $label))" :value="$value" accent="cyan" />
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="dgcpt-surface p-6">
                <p class="dgcpt-card-title">Enterprise Risk Intelligence</p>
                <h2 class="text-xl font-bold text-[#E6EEF8]">Maturité consolidée</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <x-ui.kpi-card label="Score" :value="data_get($snapshot, 'intelligence.maturity.score', 0)" accent="violet" />
                    <x-ui.kpi-card label="Compliance rate" :value="data_get($snapshot, 'intelligence.maturity.compliance_rate', 0).'%' " accent="green" />
                    <x-ui.kpi-card label="Risk maturity" :value="data_get($snapshot, 'intelligence.maturity.risk_maturity', 'emerging')" accent="yellow" />
                </div>
            </div>

            <div class="dgcpt-surface p-6">
                <p class="dgcpt-card-title">Recurring Risks</p>
                <h2 class="text-xl font-bold text-[#E6EEF8]">Top risques récurrents</h2>
                <div class="mt-4 space-y-3">
                    @forelse (data_get($snapshot, 'intelligence.recurring', []) as $risk)
                        <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4 text-sm text-[#E6EEF8]">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-semibold">{{ $risk['label'] }}</span>
                                <span class="text-[#73D8FF]">{{ $risk['count'] }}</span>
                            </div>
                            <p class="mt-2 text-xs text-[#9FB3C8]">Départements: {{ implode(', ', $risk['departments']) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-[#9FB3C8]">Aucune récurrence détectée.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
