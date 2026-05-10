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

        <form method="get" action="{{ route('missions.index') }}" class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
            @if(request()->filled('department'))
                <input type="hidden" name="department" value="{{ request('department') }}" />
            @endif
            <div class="min-w-[10rem] flex-1">
                <label for="filter-q" class="block text-xs font-medium text-gray-600 dark:text-gray-400">Recherche</label>
                <input id="filter-q" name="q" type="search" value="{{ request('q') }}"
                       placeholder="Organisation, description…"
                       class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
            </div>
            <div class="w-full sm:w-52">
                <label for="filter-status" class="block text-xs font-medium text-gray-600 dark:text-gray-400">Statut workflow</label>
                <select id="filter-status" name="status"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">Tous</option>
                    <option value="{{ \App\Models\Mission::STATUS_BROUILLON }}" @selected(request('status') === \App\Models\Mission::STATUS_BROUILLON)>Brouillon</option>
                    <option value="{{ \App\Models\Mission::STATUS_EN_COURS }}" @selected(request('status') === \App\Models\Mission::STATUS_EN_COURS)>En cours</option>
                    <option value="{{ \App\Models\Mission::STATUS_CLOTUREE }}" @selected(request('status') === \App\Models\Mission::STATUS_CLOTUREE)>Clôturée</option>
                    <option value="{{ \App\Models\Mission::STATUS_VALIDEE_IS }}" @selected(request('status') === \App\Models\Mission::STATUS_VALIDEE_IS)>Validée IS</option>
                    <option value="{{ \App\Models\Mission::STATUS_VALIDEE_COPRI }}" @selected(request('status') === \App\Models\Mission::STATUS_VALIDEE_COPRI)>Validée COPRI</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">
                    Filtrer
                </button>
                <a href="{{ route('missions.index', array_filter(request()->only(['department']))) }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    Réinitialiser
                </a>
            </div>
        </form>

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
