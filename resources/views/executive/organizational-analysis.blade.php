<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-8 px-0 py-2">
        <div>
            <p class="dgcpt-card-title">Executive Organization</p>
            <h1 class="dgcpt-page-title">Organizational Analysis</h1>
            <p class="mt-1 text-sm text-[#9FB3C8]">Lecture transversale des gaps de responsabilite et validations manquantes.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            @foreach ($analysis['totals'] as $label => $value)
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
                        <th class="text-left">Processus</th>
                        <th class="text-left">Responsables finaux manquants</th>
                        <th class="text-left">Missing responsible</th>
                        <th class="text-left">Participants</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($analysis['gaps'] as $gap)
                        <tr>
                            <td class="font-semibold text-[#E6EEF8]">{{ $gap['process_label'] }}</td>
                            <td>{{ $gap['missing_accountable'] ? 'Oui' : 'Non' }}</td>
                            <td>{{ $gap['missing_responsible'] ? 'Oui' : 'Non' }}</td>
                            <td>{{ $gap['participants'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
