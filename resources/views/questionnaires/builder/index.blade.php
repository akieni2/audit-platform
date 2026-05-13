<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Builder Enterprise</p>
                <h1 class="dgcpt-page-title">Questionnaire Builder</h1>
                <p class="mt-1 text-sm dgcpt-text-muted">
                    Création, structuration, versioning et publication des templates dynamiques du core officiel.
                </p>
            </div>
            <a href="{{ route('module.questionnaires') }}" class="dgcpt-btn-outline">Vue module</a>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.1fr,1.9fr]">
            <div class="dgcpt-surface space-y-4 p-6 shadow-sm">
                <div>
                    <h2 class="text-lg font-bold text-[#E6EEF8]">Nouveau template</h2>
                    <p class="mt-1 text-sm text-[#9FB3C8]">Chaque création démarre en brouillon, sans impacter le runtime entretien.</p>
                </div>

                <form method="POST" action="{{ route('questionnaire-builder.templates.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="dgcpt-label" for="builder-name">Nom</label>
                        <input id="builder-name" name="name" type="text" value="{{ old('name') }}" required class="dgcpt-input" />
                        @error('name')<p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="dgcpt-label" for="builder-slug">Slug</label>
                        <input id="builder-slug" name="slug" type="text" value="{{ old('slug') }}" class="dgcpt-input font-mono text-sm" />
                        <p class="mt-1 text-xs text-[#9FB3C8]">Généré automatiquement si vide.</p>
                    </div>
                    <div>
                        <label class="dgcpt-label" for="builder-desc">Description</label>
                        <textarea id="builder-desc" name="description" rows="3" class="dgcpt-textarea">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label class="dgcpt-label" for="builder-mission-type">Type de mission</label>
                        <input id="builder-mission-type" name="mission_type" type="text" value="{{ old('mission_type') }}" class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Périmètre départements</label>
                        <select name="department_scope[]" multiple class="mt-1 block min-h-[8rem] w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8]">
                            @foreach ($departmentOptions as $department)
                                <option value="{{ $department->id }}" @selected(collect(old('department_scope', []))->contains((string) $department->id))>
                                    {{ $department->code }} — {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="dgcpt-btn-primary w-full justify-center">Créer le brouillon</button>
                </form>
            </div>

            <div class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($templates as $template)
                        <a href="{{ route('questionnaire-builder.edit', $template) }}" class="dgcpt-surface block p-5 shadow-sm transition hover:border-[rgba(0,209,255,0.24)] hover:ring-1 hover:ring-[rgba(0,209,255,0.15)]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-[#E6EEF8]">{{ $template->name }}</p>
                                    <p class="mt-1 text-xs font-mono text-[#7E92A7]">{{ $template->slug }}</p>
                                </div>
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-[#123D2C] text-[#7EF2BE]' => $template->lifecycle_status === \App\Models\QuestionnaireTemplate::STATUS_PUBLISHED,
                                    'bg-[#2A2140] text-[#C9AEFF]' => $template->lifecycle_status === \App\Models\QuestionnaireTemplate::STATUS_DRAFT,
                                    'bg-[#4A3314] text-[#FFD479]' => $template->lifecycle_status === \App\Models\QuestionnaireTemplate::STATUS_DEPRECATED,
                                    'bg-[#3A1A20] text-[#FFB4B4]' => $template->lifecycle_status === \App\Models\QuestionnaireTemplate::STATUS_ARCHIVED,
                                ])>
                                    {{ $template->lifecycleLabel() }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-2 text-sm text-[#9FB3C8]">
                                <p>Version <span class="font-semibold text-[#E6EEF8]">v{{ $template->version }}</span></p>
                                <p>{{ $template->sections_count }} section(s) · {{ $template->sections->sum(fn ($section) => $section->questions->count()) }} question(s)</p>
                                <p>{{ $template->entretiens_count }} entretien(s) rattaché(s)</p>
                                @if ($template->sourceTemplate)
                                    <p>Source v{{ $template->sourceTemplate->version }} · {{ $template->sourceTemplate->name }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                {{ $templates->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
