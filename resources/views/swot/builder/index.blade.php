<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Strategic Governance</p>
                <h1 class="dgcpt-page-title">SWOT Builder</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">Templates SWOT dynamiques pour mission, departement et lecture nationale.</p>
            </div>
            <a href="{{ route('swot.consolidation') }}" class="dgcpt-btn-outline">Consolidation SWOT</a>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.2fr,0.8fr]">
            <div class="grid gap-4 md:grid-cols-2">
                @forelse ($templates as $template)
                    <div class="dgcpt-surface p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-lg font-bold text-[#E6EEF8]">{{ $template->name }}</p>
                                <p class="mt-1 text-xs text-[#9FB3C8]">{{ $template->department?->code ?? 'National' }} · {{ $template->analysis_scope }}</p>
                            </div>
                            <span class="rounded-full bg-[rgba(0,209,255,0.08)] px-3 py-1 text-xs font-semibold text-[#73D8FF]">{{ $template->lifecycleLabel() }}</span>
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                            <div class="rounded-2xl bg-[rgba(255,255,255,0.03)] p-3 text-center text-[#BFD2E6]">
                                <p class="text-xs text-[#9FB3C8]">Categories</p>
                                <p class="mt-1 text-xl font-bold text-[#E6EEF8]">{{ $template->categories_count }}</p>
                            </div>
                            <div class="rounded-2xl bg-[rgba(255,255,255,0.03)] p-3 text-center text-[#BFD2E6]">
                                <p class="text-xs text-[#9FB3C8]">Entrees</p>
                                <p class="mt-1 text-xl font-bold text-[#E6EEF8]">{{ $template->entries_count }}</p>
                            </div>
                            <div class="rounded-2xl bg-[rgba(255,255,255,0.03)] p-3 text-center text-[#BFD2E6]">
                                <p class="text-xs text-[#9FB3C8]">Analyses</p>
                                <p class="mt-1 text-xl font-bold text-[#E6EEF8]">{{ $template->analyses_count }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('swot-builder.edit', $template) }}" class="dgcpt-btn-primary">Ouvrir le builder</a>
                        </div>
                    </div>
                @empty
                    <div class="dgcpt-surface p-6 text-sm text-[#9FB3C8]">Aucun template SWOT disponible.</div>
                @endforelse
            </div>

            <div class="dgcpt-surface p-6 shadow-sm">
                <p class="dgcpt-card-title">Nouveau template</p>
                <h2 class="text-xl font-bold text-[#E6EEF8]">Creer un SWOT</h2>
                <form method="POST" action="{{ route('swot-builder.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="dgcpt-label">Nom</label>
                        <input name="name" type="text" required class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Slug</label>
                        <input name="slug" type="text" class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Code</label>
                        <input name="code" type="text" class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Departement</label>
                        <select name="department_id" class="dgcpt-input">
                            <option value="">National</option>
                            @foreach ($departmentOptions as $department)
                                <option value="{{ $department->id }}">{{ $department->code }} - {{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="dgcpt-label">Scope</label>
                        <select name="analysis_scope" class="dgcpt-input">
                            <option value="mission">Mission</option>
                            <option value="department">Departement</option>
                            <option value="national">National</option>
                        </select>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-[#BFD2E6]">
                        <input name="is_global" type="checkbox" value="1" class="rounded border-[rgba(0,209,255,0.2)] bg-[#050816]" />
                        Template global
                    </label>
                    <button type="submit" class="dgcpt-btn-primary">Creer le template</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
