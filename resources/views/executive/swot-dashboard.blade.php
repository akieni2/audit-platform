<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <div>
            <p class="dgcpt-card-title">Executive SWOT</p>
            <h1 class="dgcpt-page-title">Tableau de bord SWOT</h1>
            <p class="mt-1 text-sm text-[#9FB3C8]">Tendances SWOT, consolidation nationale et alignement strategique.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            @foreach ($dashboard['snapshot']['kpis'] as $label => $value)
                <div class="dgcpt-surface p-4 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-[#73D8FF]">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $label)) }}</p>
                    <p class="mt-2 text-2xl font-bold text-[#E6EEF8]">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="dgcpt-surface p-6 shadow-sm">
                <p class="dgcpt-card-title">Consolidation</p>
                <div class="mt-4 space-y-3">
                    @foreach ($dashboard['consolidation']['rows'] as $row)
                        <div class="rounded-2xl bg-[rgba(255,255,255,0.03)] p-4">
                            <p class="text-sm font-semibold text-[#E6EEF8]">{{ $row['department']->code }} - {{ $row['department']->name }}</p>
                            <p class="mt-1 text-xs text-[#9FB3C8]">Analyses: {{ $row['analyses_count'] }} · Score moyen: {{ $row['average_score'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="dgcpt-surface p-6 shadow-sm">
                <p class="dgcpt-card-title">Alignment</p>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    @foreach ($dashboard['alignment'] as $label => $value)
                        <div class="rounded-2xl bg-[rgba(255,255,255,0.03)] p-4">
                            <p class="text-xs uppercase tracking-wide text-[#73D8FF]">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $label)) }}</p>
                            <p class="mt-2 text-xl font-bold text-[#E6EEF8]">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="dgcpt-surface p-6 shadow-sm">
            <p class="dgcpt-card-title">Trends</p>
            <div class="mt-4 overflow-x-auto">
                <table class="dgcpt-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Mois</th>
                            <th class="text-left">Analyses</th>
                            <th class="text-left">Score moyen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dashboard['trends'] as $trend)
                            <tr>
                                <td class="font-semibold text-[#E6EEF8]">{{ $trend['month'] }}</td>
                                <td>{{ $trend['count'] }}</td>
                                <td>{{ $trend['average_score'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-[#9FB3C8]">Aucune tendance SWOT disponible.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
