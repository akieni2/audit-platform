<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 py-10 space-y-4">
        <p class="dgcpt-card-title">Mission</p>
        <h1 class="dgcpt-page-title">Constats d'audit</h1>
        <p class="text-sm text-[#9FB3C8]">
            Organisation : <strong class="text-[#E6EEF8]">{{ $mission->organisation }}</strong>
        </p>
        <p class="text-sm text-[#9FB3C8]">
            Les constats détaillés sont saisis au niveau des risques et contrôles. Utilisez la cartographie et les fiches risque pour compléter cette mission.
        </p>
        <p>
            <a href="{{ route('missions.show', $mission) }}" class="dgcpt-link text-sm">← Retour fiche mission</a>
        </p>
    </div>
</x-app-layout>
