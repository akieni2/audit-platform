<x-app-layout>
    @php
        $actor = auth()->user();
        $canGovern = $actor && $actor->can('governMission', $mission);
        $canContent = $actor && $actor->can('updateMissionContent', $mission);
    @endphp
    <div class="mx-auto max-w-3xl space-y-6 px-0 py-2">
        <div>
            <p class="dgcpt-card-title">Opérations</p>
            <h1 class="dgcpt-page-title">Modifier la mission</h1>
            <p class="mt-1 text-sm dgcpt-text-muted">
                @if ($canGovern)
                    Gouvernance départementale — ordre de mission, délais et rattachements.
                @else
                    Contribution opérationnelle — description et observations (délais verrouillés).
                @endif
            </p>
        </div>

        <form method="POST" action="{{ route('missions.update', $mission) }}" class="dgcpt-surface space-y-4 p-6 shadow-sm">
            @csrf
            @method('PUT')

            @if ($canGovern)
                <div>
                    <label class="dgcpt-label" for="edit-organisation">Intitulé / organisation</label>
                    <input id="edit-organisation" type="text" name="organisation" value="{{ old('organisation', $mission->organisation) }}" required class="dgcpt-input @error('organisation') border-red-500 ring-1 ring-red-500 @enderror" />
                    @error('organisation')
                        <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="dgcpt-label" for="edit-reference">Référence mission</label>
                        <input id="edit-reference" type="text" name="reference" value="{{ old('reference', $mission->reference) }}" class="dgcpt-input @error('reference') border-red-500 ring-1 ring-red-500 @enderror" />
                        @error('reference')
                            <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="dgcpt-label" for="edit-periode">Période d’audit</label>
                        <input id="edit-periode" type="text" name="periode_audit" value="{{ old('periode_audit', $mission->periode_audit) }}" class="dgcpt-input @error('periode_audit') border-red-500 ring-1 ring-red-500 @enderror" />
                        @error('periode_audit')
                            <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="dgcpt-label" for="edit-objet">Objet</label>
                    <textarea id="edit-objet" name="objet" rows="2" class="dgcpt-textarea @error('objet') border-red-500 ring-1 ring-red-500 @enderror">{{ old('objet', $mission->objet) }}</textarea>
                    @error('objet')
                        <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="dgcpt-label" for="edit-ordre-ref">Référence ordre de mission</label>
                        <input id="edit-ordre-ref" type="text" name="ordre_mission_reference" value="{{ old('ordre_mission_reference', $mission->ordre_mission_reference) }}" class="dgcpt-input @error('ordre_mission_reference') border-red-500 ring-1 ring-red-500 @enderror" />
                        @error('ordre_mission_reference')
                            <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="dgcpt-label" for="edit-date-ordre">Date ordre de mission</label>
                        <input id="edit-date-ordre" type="date" name="date_ordre_mission" value="{{ old('date_ordre_mission', $mission->date_ordre_mission?->format('Y-m-d')) }}" class="dgcpt-input @error('date_ordre_mission') border-red-500 ring-1 ring-red-500 @enderror" />
                        @error('date_ordre_mission')
                            <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="dgcpt-label" for="edit-date-debut">Date début</label>
                        <input id="edit-date-debut" type="date" name="date_debut" value="{{ old('date_debut', $mission->date_debut?->format('Y-m-d')) }}" required class="dgcpt-input @error('date_debut') border-red-500 ring-1 ring-red-500 @enderror" />
                        @error('date_debut')
                            <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="dgcpt-label" for="edit-date-fin">Date fin</label>
                        <input id="edit-date-fin" type="date" name="date_fin" value="{{ old('date_fin', $mission->date_fin?->format('Y-m-d')) }}" class="dgcpt-input @error('date_fin') border-red-500 ring-1 ring-red-500 @enderror" />
                        @error('date_fin')
                            <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="dgcpt-label" for="edit-deadline">Échéance (deadline)</label>
                    <input id="edit-deadline" type="date" name="deadline" value="{{ old('deadline', $mission->deadline?->format('Y-m-d')) }}" class="dgcpt-input @error('deadline') border-red-500 ring-1 ring-red-500 @enderror" />
                    @error('deadline')
                        <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            @if ($canGovern || $canContent)
                <div>
                    <label class="dgcpt-label" for="edit-description">Description</label>
                    <textarea id="edit-description" name="description" rows="4" class="dgcpt-textarea @error('description') border-red-500 ring-1 ring-red-500 @enderror">{{ old('description', $mission->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="dgcpt-label" for="edit-observations">Observations générales</label>
                    <textarea id="edit-observations" name="observations_generales" rows="3" class="dgcpt-textarea @error('observations_generales') border-red-500 ring-1 ring-red-500 @enderror">{{ old('observations_generales', $mission->observations_generales) }}</textarea>
                    @error('observations_generales')
                        <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            @if (! $canGovern && $canContent)
                <div>
                    <label class="dgcpt-label" for="edit-objet-agent">Objet</label>
                    <textarea id="edit-objet-agent" name="objet" rows="2" class="dgcpt-textarea @error('objet') border-red-500 ring-1 ring-red-500 @enderror">{{ old('objet', $mission->objet) }}</textarea>
                    @error('objet')
                        <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="dgcpt-btn-primary">Enregistrer</button>
                <a href="{{ route('missions.show', $mission) }}" class="dgcpt-btn-outline">Annuler</a>
            </div>
        </form>
    </div>
</x-app-layout>
