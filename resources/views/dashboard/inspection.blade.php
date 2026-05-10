<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-10 space-y-10">
        <header class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400">Inspection des Services</p>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Consolidation et validation</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Supervision nationale : missions à contrôler, files de validation IS / COPRI, indicateurs critiques.
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

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Validations IS en attente</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">Missions au statut « clôturée », à examiner avant validation Inspection.</p>
            <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold">Mission</th>
                            <th class="px-4 py-2 text-left font-semibold">Pôle</th>
                            <th class="px-4 py-2 text-left font-semibold">Mis à jour</th>
                            <th class="px-4 py-2 text-left font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($awaitingIs as $m)
                            <tr>
                                <td class="px-4 py-2">{{ $m->organisation }}</td>
                                <td class="px-4 py-2">{{ $m->department?->code ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $m->updated_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('missions.show', $m) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Traiter</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">Aucune mission en file IS.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Validation COPRI en attente</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">Missions au statut « validée_IS ».</p>
            <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold">Mission</th>
                            <th class="px-4 py-2 text-left font-semibold">Pôle</th>
                            <th class="px-4 py-2 text-left font-semibold">Mis à jour</th>
                            <th class="px-4 py-2 text-left font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($awaitingCopri as $m)
                            <tr>
                                <td class="px-4 py-2">{{ $m->organisation }}</td>
                                <td class="px-4 py-2">{{ $m->department?->code ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $m->updated_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('missions.show', $m) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Traiter</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">Aucune mission en file COPRI.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <p class="text-sm text-gray-500">
            <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:underline dark:text-indigo-400">← Tableau de bord départemental</a>
        </p>
    </div>
</x-app-layout>
