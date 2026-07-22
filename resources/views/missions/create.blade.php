<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-0 py-2">
        <div>
            <p class="dgcpt-card-title">Opérations</p>
            <h1 class="dgcpt-page-title">Nouvelle mission d’audit</h1>
            <p class="mt-1 text-sm dgcpt-text-muted">Création — ordre de mission et périmètre départemental.</p>
        </div>

        <form method="POST" action="{{ route('missions.store') }}" class="dgcpt-surface space-y-4 p-6 shadow-sm">
            @csrf
            <input type="hidden" name="creation_token" value="{{ $creationToken }}" />

            <div>
                <label class="dgcpt-label" for="mission-organisation">Intitulé / organisation</label>
                <input id="mission-organisation" type="text" name="organisation" value="{{ old('organisation') }}" required class="dgcpt-input @error('organisation') border-red-500 ring-1 ring-red-500 @enderror" />
                @error('organisation')
                    <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="dgcpt-label" for="mission-reference">Référence mission</label>
                    <input id="mission-reference" type="text" name="reference" value="{{ old('reference') }}" class="dgcpt-input @error('reference') border-red-500 ring-1 ring-red-500 @enderror" />
                    @error('reference')
                        <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="dgcpt-label" for="mission-periode">Période d’audit</label>
                    <input id="mission-periode" type="text" name="periode_audit" value="{{ old('periode_audit') }}" placeholder="ex. T1 2026" class="dgcpt-input @error('periode_audit') border-red-500 ring-1 ring-red-500 @enderror" />
                    @error('periode_audit')
                        <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="dgcpt-label" for="mission-objet">Objet</label>
                <textarea id="mission-objet" name="objet" rows="2" class="dgcpt-textarea @error('objet') border-red-500 ring-1 ring-red-500 @enderror">{{ old('objet') }}</textarea>
                @error('objet')
                    <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="dgcpt-label" for="mission-ordre-ref">Référence ordre de mission</label>
                    <input id="mission-ordre-ref" type="text" name="ordre_mission_reference" value="{{ old('ordre_mission_reference') }}" class="dgcpt-input @error('ordre_mission_reference') border-red-500 ring-1 ring-red-500 @enderror" />
                    @error('ordre_mission_reference')
                        <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="dgcpt-label" for="mission-date-ordre">Date ordre de mission</label>
                    <input id="mission-date-ordre" type="date" name="date_ordre_mission" value="{{ old('date_ordre_mission') }}" class="dgcpt-input @error('date_ordre_mission') border-red-500 ring-1 ring-red-500 @enderror" />
                    @error('date_ordre_mission')
                        <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="dgcpt-label" for="mission-description">Description</label>
                <textarea id="mission-description" name="description" rows="4" class="dgcpt-textarea @error('description') border-red-500 ring-1 ring-red-500 @enderror">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="dgcpt-label" for="mission-observations">Observations générales</label>
                <textarea id="mission-observations" name="observations_generales" rows="3" class="dgcpt-textarea @error('observations_generales') border-red-500 ring-1 ring-red-500 @enderror">{{ old('observations_generales') }}</textarea>
                @error('observations_generales')
                    <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="dgcpt-label" for="mission-date-debut">Date début</label>
                    <input id="mission-date-debut" type="date" name="date_debut" value="{{ old('date_debut') }}" required class="dgcpt-input @error('date_debut') border-red-500 ring-1 ring-red-500 @enderror" />
                    @error('date_debut')
                        <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="dgcpt-label" for="mission-date-fin">Date fin</label>
                    <input id="mission-date-fin" type="date" name="date_fin" value="{{ old('date_fin') }}" class="dgcpt-input @error('date_fin') border-red-500 ring-1 ring-red-500 @enderror" />
                    @error('date_fin')
                        <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="dgcpt-label" for="mission-deadline">Échéance (deadline)</label>
                <input id="mission-deadline" type="date" name="deadline" value="{{ old('deadline') }}" class="dgcpt-input @error('deadline') border-red-500 ring-1 ring-red-500 @enderror" />
                @error('deadline')
                    <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="dgcpt-btn-primary">Créer la mission</button>
                <a href="{{ route('missions.index') }}" class="dgcpt-btn-outline">Annuler</a>
            </div>
        </form>
    </div>
</x-app-layout>
