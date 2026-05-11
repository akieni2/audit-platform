<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-8 px-0 py-2">
        <header class="space-y-2">
            <p class="text-[0.65rem] font-bold uppercase tracking-[0.2em] text-dgcpt-cyan/90">Vue exécutive nationale</p>
            <h1 class="text-2xl font-extrabold uppercase tracking-wide text-slate-900 dark:text-white">Tableau de bord exécutif — Inspection des Services</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Indicateurs nationaux agrégés (missions, risques critiques, risques transversaux).
            </p>
        </header>

        <div class="dgcpt-kpi-grid">
            @foreach ($kpis as $label => $value)
                <x-ui.kpi-card :label="str_replace('_', ' ', $label)" :value="$value" />
            @endforeach
        </div>

        <x-ui.chart-card title="Synthèse KPI (national)" subtitle="Répartition relative — lecture stratégique." chart-class="h-72 max-w-xl mx-auto">
            <canvas id="executiveKpiDonut"></canvas>
        </x-ui.chart-card>

        <p class="text-sm text-slate-500 dark:text-slate-400">
            <a href="{{ route('dashboard') }}" class="font-bold text-dgcpt-cyan hover:underline">← Tableau de bord utilisateur</a>
        </p>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof Chart === 'undefined') return;
                const labels = @json(array_keys($kpis ?? []));
                const values = @json(array_values($kpis ?? []));
                const palette = ['#0A2A66', '#00D1FF', '#00A86B', '#F4D000', '#ef4444'];
                new Chart(document.getElementById('executiveKpiDonut'), {
                    type: 'doughnut',
                    data: {
                        labels: labels.map(function (l) { return l.replace(/_/g, ' '); }),
                        datasets: [{
                            data: values,
                            backgroundColor: labels.map(function (_, i) { return palette[i % palette.length]; }),
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' },
                        },
                    },
                });
            });
        </script>
    @endpush
</x-app-layout>
