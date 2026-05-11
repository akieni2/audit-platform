<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">
        <div>
            <p class="dgcpt-card-title">Structure</p>
            <h1 class="dgcpt-page-title">Nouveau département</h1>
            <p class="mt-1 text-sm">
                <a href="{{ route('admin.departments.index') }}" class="dgcpt-link">← Retour liste</a>
            </p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-[rgba(255,90,90,0.45)] bg-[#10192B] px-4 py-3 text-sm text-[#FF5A5A]">
                <ul class="ms-5 list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('admin.departments.store') }}" class="dgcpt-surface space-y-6 p-6 shadow-sm">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="dgcpt-label">Code <span class="text-[#FF5A5A]">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" required maxlength="32" class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label">Type</label>
                    <input type="text" name="type" value="{{ old('type') }}" maxlength="64" class="dgcpt-input" />
                </div>
            </div>
            <div>
                <label class="dgcpt-label">Nom <span class="text-[#FF5A5A]">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required class="dgcpt-input" />
            </div>
            <div>
                <label class="dgcpt-label">Description</label>
                <textarea name="description" rows="3" class="dgcpt-textarea">{{ old('description') }}</textarea>
            </div>
            <div>
                <label class="dgcpt-label">Superviseur référent</label>
                <select name="supervisor_user_id" class="dgcpt-select">
                    <option value="">—</option>
                    @foreach ($supervisors as $s)
                        <option value="{{ $s->id }}" @selected(old('supervisor_user_id') == $s->id)>{{ $s->displayName() }} ({{ $s->email }})</option>
                    @endforeach
                </select>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="dgcpt-label">Couleur (UI)</label>
                    <input type="text" name="accent_color" value="{{ old('accent_color') }}" placeholder="#334155 ou slate-700" class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label">Logo (chemin ou URL)</label>
                    <input type="text" name="logo_path" value="{{ old('logo_path') }}" class="dgcpt-input" />
                </div>
            </div>
            <input type="hidden" name="active" value="0" />
            <label class="inline-flex items-center gap-2 text-sm font-medium text-[#9FB3C8]">
                <input type="checkbox" name="active" value="1" class="h-4 w-4 rounded border-[rgba(0,209,255,0.35)] bg-[#050816] text-[#00D1FF] focus:ring-[#00D1FF]" @checked(old('active', true)) />
                Département actif
            </label>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="dgcpt-btn-primary">Créer</button>
                <a href="{{ route('admin.departments.index') }}" class="dgcpt-btn-outline">Annuler</a>
            </div>
        </form>
    </div>
</x-app-layout>
