<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 py-10 space-y-4">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Constats d'audit</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Mission : <strong>{{ $mission->organisation }}</strong>
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Les constats détaillés sont saisis au niveau des risques et contrôles. Utilisez la cartographie et les fiches risque pour compléter cette mission.
        </p>
        <p>
            <a href="{{ route('missions.show', $mission) }}" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400">← Retour fiche mission</a>
        </p>
    </div>
</x-app-layout>
