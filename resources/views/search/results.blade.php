<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 py-10 space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Recherche</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Missions visibles pour votre périmètre (minimum 2 caractères).</p>
        </div>

        <form method="get" action="{{ route('search') }}" class="flex flex-wrap gap-2">
            <label class="sr-only" for="q">Requête</label>
            <input id="q" name="q" type="search" value="{{ $term }}"
                   placeholder="Organisation, description…"
                   class="min-w-[12rem] flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">
                Rechercher
            </button>
        </form>

        @if (strlen($term) < 2)
            <p class="text-sm text-gray-500 dark:text-gray-400">Saisissez au moins 2 caractères pour lancer une recherche.</p>
        @elseif ($missions->isEmpty())
            <p class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300">
                Aucune mission ne correspond à « {{ $term }} ».
            </p>
        @else
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($missions as $m)
                        <li class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900/40">
                            <a href="{{ route('missions.show', $m) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                                {{ $m->organisation }}
                            </a>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $m->department?->code ?? '—' }} · {{ $m->mission_status }} · MAJ {{ $m->updated_at?->format('d/m/Y H:i') }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-app-layout>
