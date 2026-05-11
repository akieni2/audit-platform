<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 py-10 space-y-4">
        <p class="dgcpt-card-title">Mission</p>
        <h1 class="dgcpt-page-title">Constats d'audit</h1>
        <p class="text-sm dgcpt-text-muted">
            Organisation : <strong class="dgcpt-text">{{ $mission->organisation }}</strong>
        </p>
        <p class="text-sm dgcpt-text-muted">
            Les constats détaillés sont saisis au niveau des risques et contrôles. Utilisez la cartographie et les fiches risque pour compléter cette mission.
        </p>
        <p>
            <a href="{{ route('missions.show', $mission) }}" class="dgcpt-link text-sm">← Retour fiche mission</a>
        </p>
    </div>
</x-app-layout>
