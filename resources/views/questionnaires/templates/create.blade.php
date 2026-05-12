<x-app-layout>
    <div class="mx-auto max-w-2xl space-y-6 px-0 py-2">
        <div>
            <p class="dgcpt-card-title">Bibliothèque</p>
            <h1 class="dgcpt-page-title">Nouveau modèle de questionnaire</h1>
        </div>

        <form method="POST" action="{{ route('questionnaire-templates.store') }}" class="dgcpt-surface space-y-4 p-6 shadow-sm">
            @csrf
            <div>
                <label class="dgcpt-label" for="qt-name">Nom</label>
                <input id="qt-name" name="name" type="text" value="{{ old('name') }}" required class="dgcpt-input" />
                @error('name')<p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="dgcpt-label" for="qt-slug">Slug (optionnel, généré si vide)</label>
                <input id="qt-slug" name="slug" type="text" value="{{ old('slug') }}" class="dgcpt-input font-mono text-sm" />
                @error('slug')<p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="dgcpt-label" for="qt-desc">Description</label>
                <textarea id="qt-desc" name="description" rows="3" class="dgcpt-textarea">{{ old('description') }}</textarea>
            </div>
            <div>
                <label class="dgcpt-label" for="qt-mt">Type de mission (libellé métier)</label>
                <input id="qt-mt" name="mission_type" type="text" value="{{ old('mission_type') }}" placeholder="ex. audit_informatique" class="dgcpt-input" />
            </div>
            <div>
                <label class="dgcpt-label">Périmètre départements (IDs, multi-sélection)</label>
                <p class="mt-0.5 text-xs text-[#9FB3C8]">Laisser vide pour un référentiel national accessible à tous les pôles autorisés.</p>
                <select name="department_scope[]" multiple class="mt-1 block min-h-[8rem] w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8]">
                    @foreach (\App\Models\Department::query()->where('active', true)->orderBy('code')->get() as $d)
                        <option value="{{ $d->id }}" @selected(collect(old('department_scope', []))->contains((string) $d->id))>
                            {{ $d->code }} — {{ $d->name }}
                        </option>
                    @endforeach
                </select>
                @error('department_scope')<p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-3">
                <button type="submit" class="dgcpt-btn-primary">Créer</button>
                <a href="{{ route('questionnaire-templates.index') }}" class="dgcpt-btn-outline">Annuler</a>
            </div>
        </form>
    </div>
</x-app-layout>
