<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-8 px-0 py-2">
        <div>
            <p class="dgcpt-card-title">Espace d’audit de la structure</p>
            <h1 class="dgcpt-page-title">{{ $department->code }} — {{ $department->name }}</h1>
            <p class="mt-1 text-sm text-[#9FB3C8]">En tant que responsable, vous pouvez choisir ou remplacer le référentiel qui gouverne les outils d’audit de votre structure.</p>
        </div>

        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">{{ session('status') }}</div>
        @endif

        <form method="post" action="{{ route('admin.departments.update', $department) }}" class="dgcpt-surface space-y-6 p-6 shadow-sm">
            @csrf
            @method('patch')

            <div>
                <label class="dgcpt-label">Référentiel d’audit <span class="text-red-400">*</span></label>
                <select name="default_methodology_template_id" class="dgcpt-select" required>
                    <option value="">Choisir le référentiel</option>
                    @foreach ($methodologies as $methodology)
                        <option value="{{ $methodology->id }}" @selected(old('default_methodology_template_id', $department->default_methodology_template_id) == $methodology->id)>{{ $methodology->name }}</option>
                    @endforeach
                </select>
                @error('default_methodology_template_id')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="dgcpt-label">Taxonomie des risques</label>
                <select name="default_taxonomy_id" class="dgcpt-select">
                    <option value="">Taxonomie nationale</option>
                    @foreach ($taxonomies as $taxonomy)
                        <option value="{{ $taxonomy->id }}" @selected(old('default_taxonomy_id', $department->default_taxonomy_id) == $taxonomy->id)>{{ $taxonomy->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="rounded-lg border border-[rgba(0,209,255,0.18)] bg-[rgba(0,209,255,0.05)] p-4 text-sm text-[#BFD2E6]">
                <p class="font-semibold text-[#E6EEF8]">Modules provisionnés</p>
                <p class="mt-1">Workflow, bibliothèques de questions et de contrôles, questionnaires, cartographie des risques, RACI et SWOT.</p>
                <p class="mt-2 text-xs {{ data_get($department->intelligence_profile, 'audit_environment.status') === 'ready' ? 'text-[#00A86B]' : 'text-[#FFB020]' }}">
                    {{ data_get($department->intelligence_profile, 'audit_environment.status') === 'ready' ? 'Espace actuellement prêt.' : 'L’espace sera créé lors de l’enregistrement.' }}
                </p>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="dgcpt-btn-primary">Configurer l’espace d’audit</button>
                <a href="{{ route('admin.departments.index') }}" class="dgcpt-btn-outline">Retour</a>
            </div>
        </form>
    </div>
</x-app-layout>
