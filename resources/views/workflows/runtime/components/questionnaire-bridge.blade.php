<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="dgcpt-surface p-8 shadow-sm">
            <p class="dgcpt-card-title">Exécution du workflow</p>
            <h1 class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ $stage->name }}</h1>
            <p class="mt-3 text-sm text-[#9FB3C8]">
                Ce stage utilise le runtime questionnaire historique pour préserver la compatibilité avec `Entretien` et `QuestionnaireRuntimeService`.
            </p>

            <div class="mt-6 grid gap-4 rounded-2xl border border-[rgba(0,209,255,0.12)] bg-[rgba(5,8,22,0.72)] p-5 text-sm text-[#BFD2E6]">
                <p><span class="font-semibold text-[#E6EEF8]">Mission :</span> #{{ $instance->mission_id }}</p>
                <p><span class="font-semibold text-[#E6EEF8]">Questionnaire lié :</span> {{ $stage->questionnaireTemplate?->name ?? 'Non renseigné' }}</p>
                <p><span class="font-semibold text-[#E6EEF8]">Entretien lié :</span> {{ $entretien?->id ? 'Entretien #'.$entretien->id : 'Aucun entretien trouvé pour ce stage.' }}</p>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                @if ($questionnaireUrl)
                    <a href="{{ $questionnaireUrl }}" class="dgcpt-btn-primary">Ouvrir l’exécution du questionnaire</a>
                @endif
                <a href="{{ route('missions.show', $instance->mission_id) }}" class="dgcpt-btn-outline">Retour mission</a>
            </div>
        </div>
    </div>
</x-app-layout>
