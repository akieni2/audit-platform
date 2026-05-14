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
                <h1 class="dgcpt-page-title">{{ $template->name }}</h1>
                <p class="mt-1 text-sm font-mono text-[#9FB3C8]">{{ $template->slug }} · v{{ $template->version }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('form-builder.index') }}" class="dgcpt-btn-outline">Retour bibliothèque</a>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.1fr,1.3fr,1.2fr]">
            <div class="space-y-6">
                <div class="dgcpt-surface space-y-4 p-6 shadow-sm">
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
                        <span class="rounded-full bg-[#173050] px-2.5 py-1 text-xs font-semibold text-[#73D8FF]">{{ $template->component_key ?: 'dynamic_form' }}</span>
                        @if ($template->isImmutable())
                            <span class="rounded-full bg-[#173050] px-2.5 py-1 text-xs font-semibold text-[#73D8FF]">Immutable</span>
                        @endif
                    </div>

                    <div class="grid gap-3 text-sm text-[#9FB3C8] sm:grid-cols-2">
                        <p><span class="font-semibold text-[#E6EEF8]">Champs :</span> {{ $template->fields->count() }}</p>
                        <p><span class="font-semibold text-[#E6EEF8]">Soumissions :</span> {{ $template->submissions()->count() }}</p>
                        <p><span class="font-semibold text-[#E6EEF8]">Component key :</span> {{ $template->component_key ?: 'dynamic_form' }}</p>
                        <p><span class="font-semibold text-[#E6EEF8]">Signature :</span> <span class="font-mono text-xs">{{ $template->signature_hash ?: '—' }}</span></p>
                    </div>

                    <form method="POST" action="{{ route('form-builder.update', $template) }}" class="space-y-4">
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
                            <label class="dgcpt-label">Component key</label>
                            <input name="component_key" type="text" value="{{ old('component_key', $template->component_key ?: 'dynamic_form') }}" class="dgcpt-input font-mono text-sm" />
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
                        <div>
                            <label class="dgcpt-label">Description</label>
                            <textarea name="description" rows="3" class="dgcpt-textarea">{{ old('description', $template->description) }}</textarea>
                        </div>
                        <button type="submit" class="dgcpt-btn-primary">Enregistrer le formulaire</button>
                    </form>

                    <div class="flex flex-wrap gap-3 border-t border-[rgba(0,209,255,0.12)] pt-4">
                        @if ($template->lifecycle_status !== \App\Models\FormTemplate::STATUS_PUBLISHED)
                            <form method="POST" action="{{ route('form-builder.publish', $template) }}">
                                @csrf
                                <button type="submit" class="dgcpt-btn-primary">Publier</button>
                            </form>
                        @endif

                        @if ($template->lifecycle_status !== \App\Models\FormTemplate::STATUS_ARCHIVED)
                            <form method="POST" action="{{ route('form-builder.archive', $template) }}" onsubmit="return confirm('Archiver ce formulaire ?');">
                                @csrf
                                <button type="submit" class="rounded-lg border border-[rgba(255,90,90,0.28)] px-4 py-2 text-sm font-semibold text-[#FF9B9B] hover:bg-[rgba(255,90,90,0.10)]">Archiver</button>
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
                                    <a href="{{ route('form-builder.edit', $lineageTemplate) }}" class="text-sm font-semibold text-[#73D8FF] hover:underline">Ouvrir</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <form method="POST" action="{{ route('form-builder.fields.reorder') }}" class="dgcpt-surface p-5 shadow-sm">
                    @csrf
                    <input type="hidden" name="template_id" value="{{ $template->id }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Ordre des champs</p>
                            <p class="mt-1 text-sm text-[#9FB3C8]">Ajustez les positions, puis appliquez le nouvel ordre.</p>
                        </div>
                        <button type="submit" class="dgcpt-btn-outline">Réordonner</button>
                    </div>
                    <div class="mt-4 grid gap-3">
                        @foreach ($template->fields->sortBy('sort_order') as $field)
                            <label class="rounded-xl border border-[rgba(0,209,255,0.08)] bg-[rgba(5,8,22,0.7)] p-3">
                                <span class="block text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">{{ $field->field_key }}</span>
                                <span class="mt-1 block text-sm text-[#E6EEF8]">{{ $field->label }}</span>
                                <input type="hidden" name="field_ids[]" value="{{ $field->id }}" />
                                <input name="positions[{{ $field->id }}]" type="number" min="0" value="{{ $field->sort_order }}" class="dgcpt-input mt-3" />
                            </label>
                        @endforeach
                    </div>
                </form>

                <div class="dgcpt-surface p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-[#E6EEF8]">Nouveau champ</h2>
                    <form method="POST" action="{{ route('form-builder.fields.store', $template) }}" class="mt-4 grid gap-4 md:grid-cols-2">
                        @csrf
                        <div class="md:col-span-2">
                            <label class="dgcpt-label">Libellé</label>
                            <input name="label" type="text" required class="dgcpt-input" />
                        </div>
                        <div>
                            <label class="dgcpt-label">Clé</label>
                            <input name="field_key" type="text" class="dgcpt-input font-mono text-sm" placeholder="AUTO" />
                        </div>
                        <div>
                            <label class="dgcpt-label">Type</label>
                            <select name="field_type" class="dgcpt-input">
                                @foreach ($fieldTypeLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="dgcpt-label">Placeholder</label>
                            <input name="placeholder" type="text" class="dgcpt-input" />
                        </div>
                        <div>
                            <label class="dgcpt-label">Ordre</label>
                            <input name="sort_order" type="number" min="0" value="{{ $template->fields->count() }}" class="dgcpt-input" />
                        </div>
                        <div class="md:col-span-2 text-sm text-[#BFD2E6]">
                            <label class="inline-flex items-center gap-2">
                                <input name="is_required" type="checkbox" value="1" class="rounded border-[rgba(0,209,255,0.2)] bg-[#050816]" />
                                Champ requis
                            </label>
                        </div>
                        <div class="md:col-span-2">
                            <label class="dgcpt-label">Options (une ligne = `label|value`)</label>
                            <textarea name="options_text" rows="4" class="dgcpt-textarea font-mono text-xs" placeholder="Oui|yes&#10;Non|no"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="dgcpt-btn-primary">Ajouter le champ</button>
                        </div>
                    </form>
                </div>

                <div class="space-y-4">
                    @forelse ($template->fields->sortBy('sort_order') as $field)
                        @include('forms.builder.partials.field-card', ['field' => $field, 'template' => $template, 'selectedField' => $selectedField])
                    @empty
                        <div class="dgcpt-surface p-8 text-center text-sm text-[#9FB3C8] shadow-sm">
                            Aucun champ défini pour ce formulaire.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="space-y-6">
                <div class="dgcpt-surface p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-[#E6EEF8]">Configuration du champ</h2>
                    @if ($selectedField)
                        @php
                            $optionsText = $selectedField->options
                                ->sortBy('sort_order')
                                ->map(fn ($option) => $option->label.'|'.$option->value)
                                ->implode(PHP_EOL);
                            $defaultValueText = old('default_value_text');
                            if ($defaultValueText === null) {
                                $defaultValue = $selectedField->resolvedDefaultValue();
                                $defaultValueText = is_scalar($defaultValue) || $defaultValue === null
                                    ? (string) ($defaultValue ?? '')
                                    : json_encode($defaultValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            }
                        @endphp
                        <form method="POST" action="{{ route('form-builder.fields.update', $selectedField) }}" class="mt-4 space-y-4">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="dgcpt-label">Libellé</label>
                                <input name="label" type="text" value="{{ old('label', $selectedField->label) }}" required class="dgcpt-input" />
                            </div>
                            <div>
                                <label class="dgcpt-label">Clé</label>
                                <input name="field_key" type="text" value="{{ old('field_key', $selectedField->field_key) }}" required class="dgcpt-input font-mono text-sm" />
                            </div>
                            <div>
                                <label class="dgcpt-label">Type</label>
                                <select name="field_type" class="dgcpt-input">
                                    @foreach ($fieldTypeLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('field_type', $selectedField->field_type) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="dgcpt-label">Placeholder</label>
                                <input name="placeholder" type="text" value="{{ old('placeholder', $selectedField->placeholder) }}" class="dgcpt-input" />
                            </div>
                            <div>
                                <label class="dgcpt-label">Valeur par défaut</label>
                                <textarea name="default_value_text" rows="2" class="dgcpt-textarea font-mono text-xs">{{ $defaultValueText }}</textarea>
                            </div>
                            <div>
                                <label class="dgcpt-label">Options (`label|value`)</label>
                                <textarea name="options_text" rows="4" class="dgcpt-textarea font-mono text-xs">{{ old('options_text', $optionsText) }}</textarea>
                            </div>
                            <div>
                                <label class="dgcpt-label">Validation rules JSON</label>
                                <textarea name="validation_rules_json_text" rows="5" class="dgcpt-textarea font-mono text-xs">{{ old('validation_rules_json_text', json_encode($selectedField->validation_rules_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea>
                            </div>
                            <div>
                                <label class="dgcpt-label">Conditional rules JSON</label>
                                <textarea name="conditional_rules_json_text" rows="5" class="dgcpt-textarea font-mono text-xs">{{ old('conditional_rules_json_text', json_encode($selectedField->conditional_rules_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea>
                            </div>
                            <div>
                                <label class="dgcpt-label">Configuration JSON</label>
                                <textarea name="configuration_json_text" rows="5" class="dgcpt-textarea font-mono text-xs">{{ old('configuration_json_text', json_encode($selectedField->configuration_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea>
                            </div>
                            <div>
                                <label class="dgcpt-label">Aide</label>
                                <textarea name="help_text" rows="3" class="dgcpt-textarea">{{ old('help_text', $selectedField->help_text) }}</textarea>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="dgcpt-label">Ordre</label>
                                    <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $selectedField->sort_order) }}" class="dgcpt-input" />
                                </div>
                                <div class="flex items-end">
                                    <label class="inline-flex items-center gap-2 text-sm text-[#BFD2E6]">
                                        <input name="active" type="checkbox" value="1" @checked(old('active', $selectedField->active)) class="rounded border-[rgba(0,209,255,0.2)] bg-[#050816]" />
                                        Champ actif
                                    </label>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm text-[#BFD2E6]">
                                <label class="inline-flex items-center gap-2">
                                    <input name="is_required" type="checkbox" value="1" @checked(old('is_required', $selectedField->is_required)) class="rounded border-[rgba(0,209,255,0.2)] bg-[#050816]" />
                                    Champ requis
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input name="is_repeatable" type="checkbox" value="1" @checked(old('is_repeatable', $selectedField->is_repeatable)) class="rounded border-[rgba(0,209,255,0.2)] bg-[#050816]" />
                                    Champ répétable
                                </label>
                            </div>
                            <button type="submit" class="dgcpt-btn-primary">Enregistrer le champ</button>
                        </form>

                        <form method="POST" action="{{ route('form-builder.fields.destroy', $selectedField) }}" class="mt-3" onsubmit="return confirm('Supprimer ce champ ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg border border-[rgba(255,90,90,0.28)] px-4 py-2 text-sm font-semibold text-[#FF9B9B] hover:bg-[rgba(255,90,90,0.10)]">Supprimer le champ</button>
                        </form>
                    @else
                        <p class="mt-3 text-sm text-[#9FB3C8]">Sélectionnez un champ pour modifier ses options, validations, valeurs par défaut et règles conditionnelles.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
