<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Concepteur SWOT</p>
                <h1 class="dgcpt-page-title">{{ $template->name }}</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">{{ $template->department?->code ?? 'National' }} · {{ $template->analysis_scope }} · v{{ $template->version }}</p>
            </div>
            <a href="{{ route('swot-builder.index') }}" class="dgcpt-btn-outline">Retour bibliotheque</a>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
            <div class="space-y-6">
                <div class="dgcpt-surface p-6 shadow-sm">
                    <p class="dgcpt-card-title">Matrice SWOT</p>
                    <h2 class="text-xl font-bold text-[#E6EEF8]">Vue de conception dynamique</h2>
                    <div class="mt-5">
                        @include('swot.builder.matrix', ['builder' => $builder])
                    </div>
                </div>

                <div class="grid gap-6 xl:grid-cols-2">
                    <div class="dgcpt-surface p-6 shadow-sm">
                        <p class="dgcpt-card-title">Ajouter une categorie</p>
                        <form method="POST" action="{{ route('swot-builder.categories.store', $template) }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label class="dgcpt-label">Nom</label>
                                <input name="name" type="text" required class="dgcpt-input" />
                            </div>
                            <div>
                                <label class="dgcpt-label">Code</label>
                                <input name="code" type="text" class="dgcpt-input" />
                            </div>
                            <div>
                                <label class="dgcpt-label">Type</label>
                                <select name="category_type" class="dgcpt-input">
                                    @foreach ($categoryTypeLabels as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="dgcpt-label">Poids</label>
                                <input name="weight" type="number" step="0.1" value="1" class="dgcpt-input" />
                            </div>
                            <button type="submit" class="dgcpt-btn-primary">Ajouter categorie</button>
                        </form>
                    </div>

                    <div class="dgcpt-surface p-6 shadow-sm">
                        <p class="dgcpt-card-title">Ajouter une entree</p>
                        <form method="POST" action="{{ route('swot-builder.entries.store', $template) }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label class="dgcpt-label">Categorie</label>
                                <select name="swot_category_id" class="dgcpt-input">
                                    <option value="">Aucune</option>
                                    @foreach ($template->categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="dgcpt-label">Titre</label>
                                <input name="title" type="text" required class="dgcpt-input" />
                            </div>
                            <div>
                                <label class="dgcpt-label">Description</label>
                                <textarea name="description" rows="3" class="dgcpt-textarea"></textarea>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="dgcpt-label">Impact</label>
                                    <select name="impact_level" class="dgcpt-input">
                                        @foreach ($impactLabels as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="dgcpt-label">Priorite</label>
                                    <select name="priority_level" class="dgcpt-input">
                                        @foreach ($priorityLabels as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="dgcpt-btn-primary">Ajouter entree</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="dgcpt-surface p-6 shadow-sm">
                    <p class="dgcpt-card-title">Configuration</p>
                    <h2 class="text-xl font-bold text-[#E6EEF8]">Template SWOT</h2>
                    <form method="POST" action="{{ route('swot-builder.update', $template) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="dgcpt-label">Nom</label>
                            <input name="name" type="text" value="{{ $template->name }}" required class="dgcpt-input" />
                        </div>
                        <div>
                            <label class="dgcpt-label">Slug</label>
                            <input name="slug" type="text" value="{{ $template->slug }}" required class="dgcpt-input" />
                        </div>
                        <div>
                            <label class="dgcpt-label">Code</label>
                            <input name="code" type="text" value="{{ $template->code }}" class="dgcpt-input" />
                        </div>
                        <div>
                            <label class="dgcpt-label">Departement</label>
                            <select name="department_id" class="dgcpt-input">
                                <option value="">National</option>
                                @foreach ($departmentOptions as $department)
                                    <option value="{{ $department->id }}" @selected((int) $template->department_id === (int) $department->id)>{{ $department->code }} - {{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="dgcpt-label">Description</label>
                            <textarea name="description" rows="4" class="dgcpt-textarea">{{ $template->description }}</textarea>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="dgcpt-label">Scope</label>
                                <select name="analysis_scope" class="dgcpt-input">
                                    @foreach (['mission' => 'Mission', 'department' => 'Departement', 'national' => 'National'] as $value => $label)
                                        <option value="{{ $value }}" @selected($template->analysis_scope === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-2 pt-6 text-sm text-[#BFD2E6]">
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" name="active" value="1" @checked($template->active) class="rounded border-[rgba(0,209,255,0.2)] bg-[#050816]" />
                                    Actif
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" name="is_global" value="1" @checked($template->is_global) class="rounded border-[rgba(0,209,255,0.2)] bg-[#050816]" />
                                    Global
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="dgcpt-btn-primary">Mettre a jour</button>
                    </form>
                </div>

                <div class="dgcpt-surface p-6 shadow-sm">
                    <p class="dgcpt-card-title">Scoring live</p>
                    <h2 class="text-xl font-bold text-[#E6EEF8]">Synthese</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl bg-[rgba(255,255,255,0.03)] p-4">
                            <p class="text-xs text-[#9FB3C8]">Total score</p>
                            <p class="mt-2 text-2xl font-bold text-[#E6EEF8]">{{ $builder['summary']['total_score'] }}</p>
                        </div>
                        <div class="rounded-2xl bg-[rgba(255,255,255,0.03)] p-4">
                            <p class="text-xs text-[#9FB3C8]">Priority index</p>
                            <p class="mt-2 text-2xl font-bold text-[#E6EEF8]">{{ $builder['summary']['priority_index'] }}</p>
                        </div>
                        <div class="rounded-2xl bg-[rgba(255,255,255,0.03)] p-4 md:col-span-2">
                            <p class="text-xs text-[#9FB3C8]">Balance strategique</p>
                            <p class="mt-2 text-2xl font-bold text-[#73D8FF]">{{ $builder['summary']['strength_balance'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
