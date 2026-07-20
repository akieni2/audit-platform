<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <header class="space-y-2">
            <p class="dgcpt-card-title">Plateforme d’analyse exécutive</p>
            <h1 class="dgcpt-page-title">Comparaison des départements</h1>
            <p class="text-sm text-[#9FB3C8]">Comparaison transverse des départements sur les risques, workflows et charges de gouvernance.</p>
        </header>

        <div class="dgcpt-surface p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-[#E6EEF8]">
                    <thead>
                        <tr class="border-b border-[rgba(0,209,255,0.12)] text-left text-xs uppercase tracking-wide text-[#73D8FF]">
                            <th class="px-3 py-2">Département</th>
                            <th class="px-3 py-2">Registry</th>
                            <th class="px-3 py-2">Intake</th>
                            <th class="px-3 py-2">Critiques</th>
                            <th class="px-3 py-2">Exposition</th>
                            <th class="px-3 py-2">Transversal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (($comparison['departments'] ?? []) as $row)
                            <tr class="border-b border-[rgba(0,209,255,0.08)]">
                                <td class="px-3 py-3 font-semibold">{{ $row['department'] }}</td>
                                <td class="px-3 py-3">{{ $row['registry_count'] }}</td>
                                <td class="px-3 py-3">{{ $row['intake_count'] }}</td>
                                <td class="px-3 py-3">{{ $row['critical_open'] }}</td>
                                <td class="px-3 py-3">{{ $row['residual_exposure'] }}</td>
                                <td class="px-3 py-3">{{ $row['cross_department'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="dgcpt-surface p-6">
                <p class="dgcpt-card-title">Mission footprint</p>
                <h2 class="text-xl font-bold text-[#E6EEF8]">Volumétrie missions</h2>
                <div class="mt-4 space-y-2 text-sm text-[#BFD2E6]">
                    @foreach (($comparison['missions'] ?? []) as $row)
                        <div class="flex items-center justify-between rounded-xl border border-[rgba(0,209,255,0.08)] px-3 py-2">
                            <span>{{ $row['code'] }} — {{ $row['name'] }}</span>
                            <span class="font-semibold text-[#73D8FF]">{{ $row['missions_count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
