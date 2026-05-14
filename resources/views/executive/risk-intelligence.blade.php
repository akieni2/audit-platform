<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <header class="space-y-2">
            <p class="dgcpt-card-title">Enterprise Intelligence Layer</p>
            <h1 class="dgcpt-page-title">Risk Intelligence</h1>
            <p class="text-sm text-[#9FB3C8]">Corrélations, tendances, risques récurrents et heatmap nationale consolidée.</p>
        </header>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="dgcpt-surface p-6">
                <p class="dgcpt-card-title">Correlations</p>
                <h2 class="text-xl font-bold text-[#E6EEF8]">Clusters de risques</h2>
                <div class="mt-4 space-y-3">
                    @forelse (($intelligence['correlations'] ?? []) as $cluster)
                        <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-semibold text-[#E6EEF8]">{{ $cluster['cluster'] }}</span>
                                <span class="text-sm font-semibold text-[#73D8FF]">{{ $cluster['count'] }}</span>
                            </div>
                            <p class="mt-2 text-xs text-[#9FB3C8]">Départements: {{ implode(', ', $cluster['departments']) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-[#9FB3C8]">Aucune corrélation calculée.</p>
                    @endforelse
                </div>
            </div>

            <div class="dgcpt-surface p-6">
                <p class="dgcpt-card-title">Trends</p>
                <h2 class="text-xl font-bold text-[#E6EEF8]">Tendances mensuelles</h2>
                <div class="mt-4 space-y-2 text-sm text-[#BFD2E6]">
                    @foreach (($intelligence['trends'] ?? []) as $month => $count)
                        <div class="flex items-center justify-between rounded-xl border border-[rgba(0,209,255,0.08)] px-3 py-2">
                            <span>{{ $month }}</span>
                            <span class="font-semibold text-[#73D8FF]">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="dgcpt-surface p-6">
            <p class="dgcpt-card-title">Heatmap buckets</p>
            <h2 class="text-xl font-bold text-[#E6EEF8]">Lecture critique nationale</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-4">
                @foreach ((data_get($intelligence, 'national_heatmap.heatmap.buckets', [])) as $bucket => $count)
                    <x-ui.kpi-card :label="\Illuminate\Support\Str::headline($bucket)" :value="$count" accent="cyan" />
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
