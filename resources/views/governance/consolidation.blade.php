<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <header class="space-y-2">
            <p class="dgcpt-card-title">Top management</p>
            <h1 class="dgcpt-page-title">Consolidation harmonisée DGCPT</h1>
            <p class="text-sm text-[#9FB3C8]">Lecture commune des risques, même lorsque les entités utilisent des référentiels différents.</p>
        </header>

        <div class="dgcpt-kpi-grid">
            <x-ui.kpi-card label="Départements" :value="data_get($consolidation, 'totals.departments', 0)" accent="cyan" />
            <x-ui.kpi-card label="Critiques ouverts" :value="data_get($consolidation, 'totals.critical_open', 0)" accent="danger" />
            <x-ui.kpi-card label="Registre" :value="data_get($consolidation, 'totals.registry', 0)" accent="green" />
            <x-ui.kpi-card label="Intake" :value="data_get($consolidation, 'totals.intake', 0)" accent="yellow" />
        </div>

        <div class="dgcpt-surface p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-[#E6EEF8]">
                    <thead>
                        <tr class="border-b border-[rgba(0,209,255,0.12)] text-left text-xs uppercase tracking-wide text-[#73D8FF]">
                            <th class="px-3 py-2">Département</th>
                            <th class="px-3 py-2">Registre</th>
                            <th class="px-3 py-2">Intake</th>
                            <th class="px-3 py-2">Workflows</th>
                            <th class="px-3 py-2">Référentiels</th>
                            <th class="px-3 py-2">Contrôles</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (($consolidation['rows'] ?? []) as $row)
                            <tr class="border-b border-[rgba(0,209,255,0.08)]">
                                <td class="px-3 py-3 font-semibold">{{ $row['department']->code }}</td>
                                <td class="px-3 py-3">{{ data_get($row, 'kpis.total_registry', 0) }}</td>
                                <td class="px-3 py-3">{{ data_get($row, 'kpis.total_intake', 0) }}</td>
                                <td class="px-3 py-3">{{ $row['workflow_templates'] }}</td>
                                <td class="px-3 py-3">{{ $row['methodologies'] }}</td>
                                <td class="px-3 py-3">{{ $row['control_libraries'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dgcpt-surface p-6">
            <div>
                <p class="text-lg font-bold text-[#E6EEF8]">Cartographie harmonisée par famille DGCPT</p>
                <p class="mt-1 text-sm text-[#9FB3C8]">Les risques sont rapprochés des familles communes pour éviter une lecture fragmentée par référentiel.</p>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                @foreach (($consolidation['harmonized_taxonomy'] ?? []) as $bucket)
                    <div class="rounded-lg border border-[rgba(0,209,255,0.12)] bg-[rgba(7,18,32,0.62)] p-4">
                        <p class="text-sm font-semibold text-[#E6EEF8]">{{ $bucket['term']->name }}</p>
                        <p class="mt-1 text-xs uppercase tracking-wide text-[#73D8FF]">{{ $bucket['term']->code }} · {{ $bucket['mapped_methodologies'] }} référentiels mappés</p>
                        <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                            <div>
                                <p class="text-xl font-bold text-[#E6EEF8]">{{ $bucket['official_count'] }}</p>
                                <p class="text-[11px] text-[#9FB3C8]">Registre</p>
                            </div>
                            <div>
                                <p class="text-xl font-bold text-[#E6EEF8]">{{ $bucket['intake_count'] }}</p>
                                <p class="text-[11px] text-[#9FB3C8]">Intake</p>
                            </div>
                            <div>
                                <p class="text-xl font-bold text-[#E6EEF8]">{{ $bucket['residual_exposure'] }}</p>
                                <p class="text-[11px] text-[#9FB3C8]">Exposition</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
