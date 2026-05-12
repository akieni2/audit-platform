<x-app-layout>
    @php
        /** @var \App\Models\QuestionnaireTemplate $template */
    @endphp
    <div class="mx-auto max-w-5xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Bibliothèque</p>
                <h1 class="dgcpt-page-title">{{ $template->name }}</h1>
                <p class="text-sm font-mono text-[#9FB3C8]">{{ $template->slug }} · v{{ $template->version }}</p>
            </div>
            <a href="{{ route('questionnaire-templates.index') }}" class="dgcpt-btn-outline">← Liste</a>
        </div>

        @can('update', $template)
            <form method="POST" action="{{ route('questionnaire-templates.update', $template) }}" class="dgcpt-surface space-y-4 p-6 shadow-sm">
                @csrf
                @method('PUT')
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="dgcpt-label" for="e-name">Nom</label>
                        <input id="e-name" name="name" type="text" value="{{ old('name', $template->name) }}" required class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label" for="e-slug">Slug</label>
                        <input id="e-slug" name="slug" type="text" value="{{ old('slug', $template->slug) }}" required class="dgcpt-input font-mono text-sm" />
                    </div>
                </div>
                <div>
                    <label class="dgcpt-label" for="e-desc">Description</label>
                    <textarea id="e-desc" name="description" rows="2" class="dgcpt-textarea">{{ old('description', $template->description) }}</textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="dgcpt-label" for="e-mt">Type mission</label>
                        <input id="e-mt" name="mission_type" type="text" value="{{ old('mission_type', $template->mission_type) }}" class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label" for="e-ver">Version</label>
                        <input id="e-ver" name="version" type="number" min="1" value="{{ old('version', $template->version) }}" class="dgcpt-input" />
                    </div>
                </div>
                <div>
                    <label class="dgcpt-label">Périmètre départements</label>
                    <select name="department_scope[]" multiple class="mt-1 block min-h-[6rem] w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8]">
                        @foreach (\App\Models\Department::query()->where('active', true)->orderBy('code')->get() as $d)
                            <option value="{{ $d->id }}" @selected(collect(old('department_scope', $template->department_scope ?? []))->contains($d->id))>
                                {{ $d->code }} — {{ $d->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <input id="e-active" type="checkbox" name="active" value="1" class="rounded border-[rgba(0,209,255,0.35)]" @checked(old('active', $template->active)) />
                    <label for="e-active" class="text-sm text-[#E6EEF8]">Modèle actif</label>
                </div>
                <button type="submit" class="dgcpt-btn-primary">Enregistrer métadonnées</button>
            </form>

            <div class="dgcpt-surface p-6 shadow-sm">
                <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Nouvelle section</h2>
                <form method="POST" action="{{ route('questionnaire-templates.sections.store', $template) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <div class="sm:col-span-2">
                        <label class="dgcpt-label">Titre</label>
                        <input name="title" type="text" required class="dgcpt-input" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="dgcpt-label">Description</label>
                        <textarea name="description" rows="2" class="dgcpt-textarea"></textarea>
                    </div>
                    <div>
                        <label class="dgcpt-label">Ordre</label>
                        <input name="sort_order" type="number" min="0" value="0" class="dgcpt-input" />
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="dgcpt-btn-primary w-full sm:w-auto">Ajouter section</button>
                    </div>
                </form>
            </div>
        @endcan

        @foreach ($template->sections as $section)
            <div class="dgcpt-surface border-[rgba(0,209,255,0.12)] p-6 shadow-sm ring-1 ring-[rgba(0,209,255,0.08)]">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-[#E6EEF8]">{{ $section->title }}</h2>
                        @if ($section->description)
                            <p class="mt-1 text-sm text-[#9FB3C8]">{{ $section->description }}</p>
                        @endif
                    </div>
                    @can('update', $template)
                        <form method="POST" action="{{ route('questionnaire-templates.sections.destroy', [$template, $section]) }}" onsubmit="return confirm('Supprimer cette section et ses questions ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-[#FF5A5A] hover:underline">Supprimer section</button>
                        </form>
                    @endcan
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="dgcpt-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Code</th>
                                <th class="text-left">Question</th>
                                <th class="text-left">Type</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($section->questions as $q)
                                <tr>
                                    <td class="font-mono text-[#9FB3C8]">{{ $q->code ?: '—' }}</td>
                                    <td class="text-[#E6EEF8]">{{ $q->question }}</td>
                                    <td class="text-[#9FB3C8]">{{ $q->question_type }}</td>
                                    <td class="text-right">
                                        @can('update', $template)
                                            <form method="POST" action="{{ route('questionnaire-templates.questions.destroy', [$template, $section, $q]) }}" class="inline" onsubmit="return confirm('Archiver cette question ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-[#FF5A5A] hover:underline">Archiver</button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @can('update', $template)
                    <form method="POST" action="{{ route('questionnaire-templates.questions.store', [$template, $section]) }}" class="mt-6 space-y-3 border-t border-[rgba(0,209,255,0.12)] pt-6">
                        @csrf
                        <p class="dgcpt-card-title">Ajouter une question</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="dgcpt-label">Code</label>
                                <input name="code" type="text" class="dgcpt-input font-mono text-sm" />
                            </div>
                            <div>
                                <label class="dgcpt-label">Type</label>
                                <select name="question_type" class="dgcpt-input" required>
                                    @foreach (\App\Models\QuestionnaireQuestion::questionTypes() as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="dgcpt-label">Intitulé</label>
                                <textarea name="question" rows="2" required class="dgcpt-textarea"></textarea>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="dgcpt-label">Aide</label>
                                <textarea name="help_text" rows="2" class="dgcpt-textarea"></textarea>
                            </div>
                            <div class="flex flex-wrap gap-4 text-sm text-[#E6EEF8]">
                                <label class="inline-flex items-center gap-2"><input type="checkbox" name="required" value="1" /> Obligatoire</label>
                                <label class="inline-flex items-center gap-2"><input type="checkbox" name="allows_observation" value="1" checked /> Observations</label>
                                <label class="inline-flex items-center gap-2"><input type="checkbox" name="allows_risk_detection" value="1" /> Détection risque</label>
                            </div>
                            <div>
                                <label class="dgcpt-label">Catégorie risque (optionnel)</label>
                                <input name="risk_category" type="text" class="dgcpt-input" />
                            </div>
                            <div>
                                <label class="dgcpt-label">Ordre</label>
                                <input name="sort_order" type="number" min="0" value="0" class="dgcpt-input" />
                            </div>
                        </div>
                        <button type="submit" class="dgcpt-btn-primary">Ajouter question</button>
                    </form>
                @endcan
            </div>
        @endforeach
    </div>
</x-app-layout>
