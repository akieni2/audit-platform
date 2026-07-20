<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="dgcpt-surface border-[rgba(255,90,90,0.30)] px-4 py-3 text-sm text-[#FFD4D4] ring-1 ring-[rgba(255,90,90,0.18)]">
                <p class="font-semibold">Des validations bloquent l’action.</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Concepteur institutionnel de questionnaires</p>
                <h1 class="dgcpt-page-title">{{ $template->name }}</h1>
                <p class="mt-1 text-sm font-mono text-[#9FB3C8]">{{ $template->slug }} · v{{ $template->version }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('questionnaire-builder.index') }}" class="dgcpt-btn-outline">Retour bibliothèque</a>
                <a href="{{ route('questionnaire-templates.index') }}" class="dgcpt-btn-outline">UI legacy</a>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.15fr,1.85fr]">
            <div class="space-y-6">
                <div class="dgcpt-surface space-y-4 p-6 shadow-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <span @class([
                            'rounded-full px-2.5 py-1 text-xs font-semibold',
                            'bg-[#123D2C] text-[#7EF2BE]' => $template->lifecycle_status === \App\Models\QuestionnaireTemplate::STATUS_PUBLISHED,
                            'bg-[#2A2140] text-[#C9AEFF]' => $template->lifecycle_status === \App\Models\QuestionnaireTemplate::STATUS_DRAFT,
                            'bg-[#4A3314] text-[#FFD479]' => $template->lifecycle_status === \App\Models\QuestionnaireTemplate::STATUS_DEPRECATED,
                            'bg-[#3A1A20] text-[#FFB4B4]' => $template->lifecycle_status === \App\Models\QuestionnaireTemplate::STATUS_ARCHIVED,
                        ])>
                            {{ $template->lifecycleLabel() }}
                        </span>
                        @if ($template->isImmutable())
                            <span class="rounded-full bg-[#173050] px-2.5 py-1 text-xs font-semibold text-[#73D8FF]">Immutable</span>
                        @endif
                    </div>

                    <div class="grid gap-3 text-sm text-[#9FB3C8] sm:grid-cols-2">
                        <p><span class="font-semibold text-[#E6EEF8]">Sections :</span> {{ $template->sections->count() }}</p>
                        <p><span class="font-semibold text-[#E6EEF8]">Questions :</span> {{ $template->sections->sum(fn ($section) => $section->questions->count()) }}</p>
                        <p><span class="font-semibold text-[#E6EEF8]">Mission type :</span> {{ $template->mission_type ?: '—' }}</p>
                        <p><span class="font-semibold text-[#E6EEF8]">Signature :</span> <span class="font-mono text-xs">{{ $template->signature_hash ?: '—' }}</span></p>
                    </div>

                    @if ($template->isImmutable())
                        <div class="rounded-2xl border border-[rgba(115,216,255,0.25)] bg-[rgba(12,32,58,0.65)] p-4 text-sm text-[#BFD2E6]">
                            Toute modification depuis cet écran créera automatiquement une nouvelle version brouillon pour préserver l’immuabilité du template publié.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('questionnaire-builder.templates.update', $template) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="dgcpt-label">Nom</label>
                            <input name="name" type="text" value="{{ old('name', $template->name) }}" required class="dgcpt-input" />
                        </div>
                        <div>
                            <label class="dgcpt-label">Slug</label>
                            <input name="slug" type="text" value="{{ old('slug', $template->slug) }}" required class="dgcpt-input font-mono text-sm" />
                        </div>
                        <div>
                            <label class="dgcpt-label">Description</label>
                            <textarea name="description" rows="3" class="dgcpt-textarea">{{ old('description', $template->description) }}</textarea>
                        </div>
                        <div>
                            <label class="dgcpt-label">Type mission</label>
                            <input name="mission_type" type="text" value="{{ old('mission_type', $template->mission_type) }}" class="dgcpt-input" />
                        </div>
                        <div>
                            <label class="dgcpt-label">Périmètre départements</label>
                            <select name="department_scope[]" multiple class="mt-1 block min-h-[8rem] w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8]">
                                @foreach ($departmentOptions as $department)
                                    <option value="{{ $department->id }}" @selected(collect(old('department_scope', $template->department_scope ?? []))->contains($department->id))>
                                        {{ $department->code }} — {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="dgcpt-btn-primary">Enregistrer le template</button>
                    </form>

                    <div class="flex flex-wrap gap-3 border-t border-[rgba(0,209,255,0.12)] pt-4">
                        @if ($template->lifecycle_status !== \App\Models\QuestionnaireTemplate::STATUS_PUBLISHED)
                            <form method="POST" action="{{ route('questionnaire-builder.templates.publish', $template) }}">
                                @csrf
                                <button type="submit" class="dgcpt-btn-primary">Publier</button>
                            </form>
                        @endif

                        @if ($template->lifecycle_status !== \App\Models\QuestionnaireTemplate::STATUS_ARCHIVED)
                            <form method="POST" action="{{ route('questionnaire-builder.templates.archive', $template) }}" onsubmit="return confirm('Archiver ce template ?');">
                                @csrf
                                <button type="submit" class="rounded-lg border border-[rgba(255,90,90,0.28)] px-4 py-2 text-sm font-semibold text-[#FF9B9B] hover:bg-[rgba(255,90,90,0.10)]">
                                    Archiver
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="dgcpt-surface p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-[#E6EEF8]">Historique de version</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($lineageTemplates as $lineageTemplate)
                            <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] px-4 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-[#E6EEF8]">v{{ $lineageTemplate->version }} · {{ $lineageTemplate->name }}</p>
                                        <p class="mt-1 text-xs font-mono text-[#7E92A7]">{{ $lineageTemplate->slug }}</p>
                                    </div>
                                    <a href="{{ route('questionnaire-builder.edit', $lineageTemplate) }}" class="text-sm font-semibold text-[#73D8FF] hover:underline">
                                        Ouvrir
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <form method="POST" action="{{ route('questionnaire-builder.sections.reorder') }}" class="dgcpt-surface p-5 shadow-sm">
                    @csrf
                    <input type="hidden" name="template_id" value="{{ $template->id }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Ordre des sections</p>
                            <p class="mt-1 text-sm text-[#9FB3C8]">Ajustez les positions, puis appliquez le nouvel ordre.</p>
                        </div>
                        <button type="submit" class="dgcpt-btn-outline">Réordonner les sections</button>
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        @foreach ($template->sections->sortBy('sort_order') as $section)
                            <label class="rounded-xl border border-[rgba(0,209,255,0.08)] bg-[rgba(5,8,22,0.7)] p-3">
                                <span class="block text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Section {{ $loop->iteration }}</span>
                                <span class="mt-1 block text-sm text-[#E6EEF8]">{{ $section->title }}</span>
                                <input name="positions[{{ $section->id }}]" type="number" min="0" value="{{ $section->sort_order }}" class="dgcpt-input mt-3" />
                            </label>
                        @endforeach
                    </div>
                </form>

                <div class="dgcpt-surface p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-[#E6EEF8]">Nouvelle section</h2>
                    <form method="POST" action="{{ route('questionnaire-builder.sections.store', $template) }}" class="mt-4 grid gap-4 md:grid-cols-2">
                        @csrf
                        <div class="md:col-span-2">
                            <label class="dgcpt-label">Titre</label>
                            <input name="title" type="text" required class="dgcpt-input" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="dgcpt-label">Description</label>
                            <textarea name="description" rows="2" class="dgcpt-textarea"></textarea>
                        </div>
                        <div>
                            <label class="dgcpt-label">Ordre</label>
                            <input name="sort_order" type="number" min="0" value="{{ $template->sections->count() }}" class="dgcpt-input" />
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="dgcpt-btn-primary">Ajouter la section</button>
                        </div>
                    </form>
                </div>

                <div class="space-y-5">
                    @forelse ($template->sections->sortBy('sort_order') as $section)
                        @include('questionnaires.builder.partials.section-card', ['section' => $section, 'template' => $template])
                    @empty
                        <div class="dgcpt-surface p-8 text-center text-sm text-[#9FB3C8] shadow-sm">
                            Aucune section pour ce template. Commencez par structurer le questionnaire.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
