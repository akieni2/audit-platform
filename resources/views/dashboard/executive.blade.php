<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-10 space-y-8">
        <header class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400">Vue exécutive</p>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Tableau de bord exécutif — Inspection des Services</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Indicateurs nationaux agrégés (missions, risques critiques, risques transversaux).
            </p>
        </header>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($kpis as $label => $value)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ str_replace('_', ' ', $label) }}
                    </div>
                    <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-50">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Synthèse KPI (national)</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Répartition relative — lecture stratégique.</p>
            <div class="mt-6 h-72 max-w-xl mx-auto">
                <canvas id="executiveKpiDonut"></canvas>
            </div>
        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            <a href="{{ route('dashboard') }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">← Tableau de bord utilisateur</a>
        </p>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof Chart === 'undefined') return;
                const labels = @json(array_keys($kpis ?? []));
                const values = @json(array_values($kpis ?? []));
                const palette = ['#6366f1', '#0ea5e9', '#ef4444', '#f97316', '#10b981'];
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
