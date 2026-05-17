<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Cartographie visuelle enterprise</p>
                <h1 class="dgcpt-page-title">Cartographie des risques</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">Mission : <span class="font-semibold text-[#E6EEF8]">{{ $mission->organisation }}</span></p>
            </div>
            <a href="{{ route('cartographie.select') }}" class="dgcpt-btn-outline">Changer de mission</a>
        </div>

        @include('risks.heatmap.filters', ['heatmapView' => $heatmapView])
        @include('risks.heatmap.analytics', ['heatmapView' => $heatmapView])

        <div class="grid gap-6 xl:grid-cols-[1.45fr,0.9fr]">
            <div class="space-y-6">
                @include('risks.heatmap.matrix', ['heatmapView' => $heatmapView])

                <div class="dgcpt-surface p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="dgcpt-card-title">Drilldown risques</p>
                            <h2 class="text-xl font-bold text-[#E6EEF8]">Vue détaillée mission</h2>
                        </div>
                    </div>
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-[rgba(0,209,255,0.10)] text-left text-[#9FB3C8]">
                                    <th class="px-4 py-3">Description</th>
                                    <th class="px-4 py-3">IxP</th>
                                    <th class="px-4 py-3">Crit. inh.</th>
                                    <th class="px-4 py-3">Score res.</th>
                                    <th class="px-4 py-3">Département</th>
                                    <th class="px-4 py-3">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($risques as $r)
                                    <tr class="border-b border-[rgba(255,255,255,0.04)] text-[#BFD2E6]">
                                        <td class="px-4 py-3">{{ \Illuminate\Support\Str::limit($r->description, 64) }}</td>
                                        <td class="px-4 py-3">{{ $r->impact_inherent }}x{{ $r->probabilite_inherent }}</td>
                                        <td class="px-4 py-3">{{ \App\Domain\Risk\Enums\CriticalityLevel::fromMixed($r->criticite_inherent)?->label() ?? '—' }}</td>
                                        <td class="px-4 py-3">{{ $r->score_residuel ?? '—' }}</td>
                                        <td class="px-4 py-3">{{ $r->departement ?? '—' }}</td>
                                        <td class="px-4 py-3">{{ \App\Domain\Risk\Enums\RiskStatus::tryFrom($r->statut_risque ?? '')?->label() ?? ($r->statut_risque ?? '—') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-[#9FB3C8]">Aucun risque.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @include('risks.heatmap.sidebar', ['heatmapView' => $heatmapView])
        </div>
    </div>
</x-app-layout>
