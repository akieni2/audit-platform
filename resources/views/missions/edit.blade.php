<x-app-layout>
    <div class="max-w-2xl mx-auto px-4 py-10 space-y-6">
        <p class="dgcpt-card-title">Opérations</p>
        <h1 class="dgcpt-page-title">Modifier la mission</h1>

        <form method="POST" action="{{ route('missions.update', $mission) }}" class="dgcpt-surface space-y-4 p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div>
                <label class="dgcpt-label">Organisation</label>
                <input type="text" name="organisation" value="{{ old('organisation', $mission->organisation) }}" required class="dgcpt-input" />
            </div>

            <div>
                <label class="dgcpt-label">Description</label>
                <textarea name="description" rows="5" class="dgcpt-textarea">{{ old('description', $mission->description) }}</textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="dgcpt-label">Date début</label>
                    <input type="date" name="date_debut" value="{{ old('date_debut', $mission->date_debut) }}" required class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label">Date fin</label>
                    <input type="date" name="date_fin" value="{{ old('date_fin', $mission->date_fin) }}" class="dgcpt-input" />
                </div>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="dgcpt-btn-primary">Enregistrer</button>
                <a href="{{ route('missions.show', $mission) }}" class="dgcpt-btn-outline">Annuler</a>
            </div>
        </form>
    </div>
</x-app-layout>
