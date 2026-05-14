<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <header class="space-y-2">
            <p class="dgcpt-card-title">Cross-Department Consolidation</p>
            <h1 class="dgcpt-page-title">Consolidation enterprise</h1>
            <p class="text-sm text-[#9FB3C8]">Consolidation des risques, workflows, conformités, contrôles et KPIs entre départements.</p>
        </header>

        <div class="dgcpt-kpi-grid">
            <x-ui.kpi-card label="Départements" :value="data_get($consolidation, 'totals.departments', 0)" accent="cyan" />
            <x-ui.kpi-card label="Critiques ouverts" :value="data_get($consolidation, 'totals.critical_open', 0)" accent="danger" />
            <x-ui.kpi-card label="Registry" :value="data_get($consolidation, 'totals.registry', 0)" accent="green" />
            <x-ui.kpi-card label="Intake" :value="data_get($consolidation, 'totals.intake', 0)" accent="yellow" />
        </div>

        <div class="dgcpt-surface p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-[#E6EEF8]">
                    <thead>
                        <tr class="border-b border-[rgba(0,209,255,0.12)] text-left text-xs uppercase tracking-wide text-[#73D8FF]">
                            <th class="px-3 py-2">Département</th>
                            <th class="px-3 py-2">Registry</th>
                            <th class="px-3 py-2">Intake</th>
                            <th class="px-3 py-2">Workflows</th>
                            <th class="px-3 py-2">Méthodologies</th>
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
    </div>
</x-app-layout>
