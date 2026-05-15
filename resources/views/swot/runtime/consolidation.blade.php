<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <div>
            <p class="dgcpt-card-title">Enterprise SWOT</p>
            <h1 class="dgcpt-page-title">Consolidation inter-departements</h1>
            <p class="mt-1 text-sm text-[#9FB3C8]">Vue consolidee pour le pilotage multi-departements et national.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            @foreach ($consolidation['totals'] as $label => $value)
                <div class="dgcpt-surface p-4 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-[#73D8FF]">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $label)) }}</p>
                    <p class="mt-2 text-2xl font-bold text-[#E6EEF8]">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="dgcpt-surface overflow-hidden shadow-sm">
            <table class="dgcpt-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th class="text-left">Departement</th>
                        <th class="text-left">Analyses</th>
                        <th class="text-left">Score moyen</th>
                        <th class="text-left">Recommandations</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($consolidation['rows'] as $row)
                        <tr>
                            <td class="font-semibold text-[#E6EEF8]">{{ $row['department']->code }} - {{ $row['department']->name }}</td>
                            <td>{{ $row['analyses_count'] }}</td>
                            <td>{{ $row['average_score'] }}</td>
                            <td>{{ $row['recommendations_count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
