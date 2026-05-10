<x-app-layout>
    <div class="max-w-5xl mx-auto px-4 py-10 space-y-8">
        <header class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">COPRI — Pilotage stratégique</p>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Indicateurs nationaux consolidés</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Vue agrégée uniquement : aucune donnée opérationnelle nominative n’est exposée à ce niveau.
            </p>
        </header>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($kpis as $label => $value)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ str_replace('_', ' ', $label) }}
                    </div>
                    <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-50">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100">
            Les analyses de tendance et rapports consolidés institutionnels peuvent être branchés sur ces agrégats (exports sécurisés, périmètre COPRI).
        </div>

        <p class="text-sm text-gray-500">
            <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:underline dark:text-indigo-400">← Tableau de bord opérationnel (si habilité)</a>
        </p>
    </div>
</x-app-layout>
