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
                <p class="dgcpt-card-title">Dynamic Form Engine</p>
                <h1 class="dgcpt-page-title">Bibliothèque des formulaires</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">Définissez des formulaires low-code versionnés pour les stages workflow dynamiques.</p>
            </div>
            <a href="{{ route('form-builder.create') }}" class="dgcpt-btn-primary">Nouveau formulaire</a>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.05fr,1.95fr]">
            <div class="dgcpt-surface space-y-4 p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#E6EEF8]">Créer un brouillon</h2>
                <form method="POST" action="{{ route('form-builder.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="dgcpt-label">Nom</label>
                        <input name="name" type="text" value="{{ old('name') }}" required class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Slug</label>
                        <input name="slug" type="text" value="{{ old('slug') }}" class="dgcpt-input font-mono text-sm" placeholder="auto si vide" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Component key</label>
                        <input name="component_key" type="text" value="{{ old('component_key', 'dynamic_form') }}" class="dgcpt-input font-mono text-sm" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Périmètre départements</label>
                        <select name="department_scope[]" multiple class="mt-1 block min-h-[8rem] w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8]">
                            @foreach ($departmentOptions as $department)
                                <option value="{{ $department->id }}" @selected(collect(old('department_scope', []))->contains($department->id))>
                                    {{ $department->code }} — {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="dgcpt-label">Description</label>
                        <textarea name="description" rows="4" class="dgcpt-textarea">{{ old('description') }}</textarea>
                    </div>
                    <button type="submit" class="dgcpt-btn-primary">Créer le formulaire</button>
                </form>
            </div>

            <div class="space-y-5">
                @forelse ($templates as $template)
                    <div class="dgcpt-surface p-6 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-[#123D2C] text-[#7EF2BE]' => $template->lifecycle_status === \App\Models\FormTemplate::STATUS_PUBLISHED,
                                        'bg-[#2A2140] text-[#C9AEFF]' => $template->lifecycle_status === \App\Models\FormTemplate::STATUS_DRAFT,
                                        'bg-[#4A3314] text-[#FFD479]' => $template->lifecycle_status === \App\Models\FormTemplate::STATUS_DEPRECATED,
                                        'bg-[#3A1A20] text-[#FFB4B4]' => $template->lifecycle_status === \App\Models\FormTemplate::STATUS_ARCHIVED,
                                    ])>
                                        {{ $template->lifecycleLabel() }}
                                    </span>
                                    <span class="rounded-full bg-[#173050] px-2.5 py-1 text-xs font-semibold text-[#73D8FF]">
                                        {{ $template->component_key ?: 'dynamic_form' }}
                                    </span>
                                </div>
                                <h2 class="mt-3 text-xl font-bold text-[#E6EEF8]">{{ $template->name }}</h2>
                                <p class="mt-1 font-mono text-xs text-[#7E92A7]">{{ $template->slug }} · v{{ $template->version }}</p>
                                <p class="mt-3 text-sm text-[#9FB3C8]">{{ $template->description ?: 'Aucune description.' }}</p>
                            </div>
                            <a href="{{ route('form-builder.edit', $template) }}" class="dgcpt-btn-outline">Ouvrir le builder</a>
                        </div>

                        <div class="mt-5 grid gap-3 md:grid-cols-4">
                            <div class="rounded-2xl border border-[rgba(0,209,255,0.12)] bg-[rgba(5,8,22,0.7)] p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Champs</p>
                                <p class="mt-2 text-2xl font-bold text-[#E6EEF8]">{{ $template->fields_count }}</p>
                            </div>
                            <div class="rounded-2xl border border-[rgba(0,209,255,0.12)] bg-[rgba(5,8,22,0.7)] p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Soumissions</p>
                                <p class="mt-2 text-2xl font-bold text-[#E6EEF8]">{{ $template->submissions_count }}</p>
                            </div>
                            <div class="rounded-2xl border border-[rgba(0,209,255,0.12)] bg-[rgba(5,8,22,0.7)] p-4 md:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Signature</p>
                                <p class="mt-2 truncate font-mono text-xs text-[#BFD2E6]">{{ $template->signature_hash ?: '—' }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="dgcpt-surface p-8 text-center text-sm text-[#9FB3C8] shadow-sm">
                        Aucun formulaire n’a encore été créé.
                    </div>
                @endforelse

                <div>{{ $templates->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
