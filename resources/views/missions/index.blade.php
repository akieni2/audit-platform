<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-10 space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Missions</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Workflow ascendant — rattachement départemental.</p>
            </div>
            @if (auth()->user()?->can('create', \App\Models\Mission::class))
                <a href="{{ route('missions.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">
                    Nouvelle mission
                </a>
            @endif
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Organisation</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Statut</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Début</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Fin</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($missions as $mission)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                                <a href="{{ route('missions.show', $mission) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">
                                    {{ $mission->organisation }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                <x-mission-status-badge :status="$mission->mission_status" />
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $mission->date_debut }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $mission->date_fin ?? '?' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1 text-xs">
                                    <a href="{{ route('missions.show', $mission) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">Fiche</a>
                                    <a href="{{ route('services.index', $mission) }}" class="text-gray-600 hover:underline dark:text-gray-400">Services</a>
                                    <a href="{{ route('cartographie.index', $mission) }}" class="text-gray-600 hover:underline dark:text-gray-400">Cartographie</a>
                                    <a href="{{ route('missions.rapport', $mission) }}" class="text-gray-600 hover:underline dark:text-gray-400">PDF</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Aucune mission visible pour votre périmètre.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($missions->hasPages())
            <div class="mt-6">
                {{ $missions->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
