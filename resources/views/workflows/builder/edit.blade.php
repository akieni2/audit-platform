<x-app-layout>
    @php
        $statusValue = $template->status?->value ?? $template->status;
    @endphp

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
                <p class="dgcpt-card-title">Workflow Builder Enterprise</p>
                <h1 class="dgcpt-page-title">{{ $template->name }}</h1>
                <p class="mt-1 text-sm font-mono text-[#9FB3C8]">{{ $template->slug }} · v{{ $template->version }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('workflow-builder.index') }}" class="dgcpt-btn-outline">Retour bibliothèque</a>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.2fr,1.8fr,1.1fr]">
            <div class="space-y-6">
                <div class="dgcpt-surface space-y-4 p-6 shadow-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <span @class([
                            'rounded-full px-2.5 py-1 text-xs font-semibold',
                            'bg-[#123D2C] text-[#7EF2BE]' => $statusValue === \App\Models\WorkflowTemplate::STATUS_PUBLISHED,
                            'bg-[#2A2140] text-[#C9AEFF]' => $statusValue === \App\Models\WorkflowTemplate::STATUS_DRAFT,
                            'bg-[#4A3314] text-[#FFD479]' => $statusValue === \App\Models\WorkflowTemplate::STATUS_DEPRECATED,
                            'bg-[#3A1A20] text-[#FFB4B4]' => $statusValue === \App\Models\WorkflowTemplate::STATUS_ARCHIVED,
                        ])>
                            {{ $template->lifecycleLabel() }}
                        </span>
                        @if ($template->isImmutable())
                            <span class="rounded-full bg-[#173050] px-2.5 py-1 text-xs font-semibold text-[#73D8FF]">Immutable</span>
                        @endif
                        @if ($template->department)
                            <span class="rounded-full bg-[#0A2A66] px-2.5 py-1 text-xs font-semibold text-[#73D8FF]">
                                {{ $template->department->code }}
                            </span>
                        @else
                            <span class="rounded-full bg-[#17223B] px-2.5 py-1 text-xs font-semibold text-[#BFD2E6]">National</span>
                        @endif
                    </div>

                    <div class="grid gap-3 text-sm text-[#9FB3C8] sm:grid-cols-2">
                        <p><span class="font-semibold text-[#E6EEF8]">Stages :</span> {{ $template->stages->count() }}</p>
                        <p><span class="font-semibold text-[#E6EEF8]">Transitions :</span> {{ $template->transitions->count() }}</p>
                        <p><span class="font-semibold text-[#E6EEF8]">Code :</span> {{ $template->code ?: '—' }}</p>
                        <p><span class="font-semibold text-[#E6EEF8]">Signature :</span> <span class="font-mono text-xs">{{ $template->signature_hash ?: '—' }}</span></p>
                    </div>

                    @if ($template->isImmutable())
                        <div class="rounded-2xl border border-[rgba(115,216,255,0.25)] bg-[rgba(12,32,58,0.65)] p-4 text-sm text-[#BFD2E6]">
                            Toute modification depuis cet écran créera automatiquement une nouvelle version brouillon pour préserver l’immuabilité du workflow publié.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('workflow-builder.update', $template) }}" class="space-y-4">
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
                            <label class="dgcpt-label">Code</label>
                            <input name="code" type="text" value="{{ old('code', $template->code) }}" class="dgcpt-input font-mono text-sm" />
                        </div>
                        <div>
                            <label class="dgcpt-label">Département</label>
                            <select name="department_id" class="dgcpt-input">
                                <option value="">National / transversal</option>
                                @foreach ($departmentOptions as $department)
                                    <option value="{{ $department->id }}" @selected((string) old('department_id', $template->department_id) === (string) $department->id)>
                                        {{ $department->code }} — {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="dgcpt-label">Description</label>
                            <textarea name="description" rows="4" class="dgcpt-textarea">{{ old('description', $template->description) }}</textarea>
                        </div>
                        <button type="submit" class="dgcpt-btn-primary">Enregistrer le workflow</button>
                    </form>

                    <div class="flex flex-wrap gap-3 border-t border-[rgba(0,209,255,0.12)] pt-4">
                        @if ($statusValue !== \App\Models\WorkflowTemplate::STATUS_PUBLISHED)
                            <form method="POST" action="{{ route('workflow-builder.publish', $template) }}">
                                @csrf
                                <button type="submit" class="dgcpt-btn-primary">Publier</button>
                            </form>
                        @endif

                        @if ($statusValue !== \App\Models\WorkflowTemplate::STATUS_ARCHIVED)
                            <form method="POST" action="{{ route('workflow-builder.archive', $template) }}" onsubmit="return confirm('Archiver ce workflow ?');">
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
                                    <a href="{{ route('workflow-builder.edit', $lineageTemplate) }}" class="text-sm font-semibold text-[#73D8FF] hover:underline">Ouvrir</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="dgcpt-surface p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-[#E6EEF8]">Canvas workflow</h2>
                            <p class="mt-1 text-sm text-[#9FB3C8]">Déplacez les cartes pour organiser le flux. Les coordonnées de l’étape sélectionnée peuvent ensuite être enregistrées depuis la sidebar.</p>
                        </div>
                        <a href="{{ route('workflow-builder.edit', $template) }}" class="text-sm font-semibold text-[#73D8FF] hover:underline">Réinitialiser sélection</a>
                    </div>

                    <div id="workflow-canvas" class="relative mt-5 min-h-[34rem] overflow-auto rounded-3xl border border-[rgba(0,209,255,0.12)] bg-[radial-gradient(circle_at_top,_rgba(10,42,102,0.55),_rgba(5,8,22,0.95))] p-6">
                        @forelse ($template->stages->sortBy('sort_order') as $stage)
                            @include('workflows.builder.partials.stage-card', ['stage' => $stage, 'template' => $template, 'selectedStage' => $selectedStage])
                        @empty
                            <div class="rounded-2xl border border-dashed border-[rgba(0,209,255,0.18)] p-8 text-center text-sm text-[#9FB3C8]">
                                Aucune étape pour ce workflow. Commencez par ajouter une première carte.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="dgcpt-surface p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-[#E6EEF8]">Nouvelle étape</h2>
                    <form method="POST" action="{{ route('workflow-builder.stages.store', $template) }}" class="mt-4 grid gap-4 md:grid-cols-2">
                        @csrf
                        <div class="md:col-span-2">
                            <label class="dgcpt-label">Nom</label>
                            <input name="name" type="text" required class="dgcpt-input" />
                        </div>
                        <div>
                            <label class="dgcpt-label">Code</label>
                            <input name="code" type="text" class="dgcpt-input font-mono text-sm" placeholder="MISSION" />
                        </div>
                        <div>
                            <label class="dgcpt-label">Type</label>
                            <select name="stage_type" class="dgcpt-input">
                                @foreach ($stageTypeLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="dgcpt-label">Mode d’exécution</label>
                            <select name="execution_mode" class="dgcpt-input">
                                <option value="">Déduire du type</option>
                                @foreach ($executionModeLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="dgcpt-label">Questionnaire</label>
                            <select name="questionnaire_template_id" class="dgcpt-input">
                                <option value="">Aucun</option>
                                @foreach ($questionnaireTemplates as $questionnaire)
                                    <option value="{{ $questionnaire->id }}">{{ $questionnaire->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="dgcpt-label">Formulaire</label>
                            <select name="form_template_id" class="dgcpt-input">
                                <option value="">Aucun</option>
                                @foreach ($formTemplates as $formTemplate)
                                    <option value="{{ $formTemplate->id }}">{{ $formTemplate->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="dgcpt-label">Component key</label>
                            <input name="component_key" type="text" value="dynamic_form" class="dgcpt-input font-mono text-sm" />
                        </div>
                        <div>
                            <label class="dgcpt-label">X</label>
                            <input name="position_x" type="number" value="{{ ($template->stages->count() * 240) }}" class="dgcpt-input" />
                        </div>
                        <div>
                            <label class="dgcpt-label">Y</label>
                            <input name="position_y" type="number" value="0" class="dgcpt-input" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="dgcpt-label">Description</label>
                            <textarea name="description" rows="3" class="dgcpt-textarea"></textarea>
                        </div>
                        <div class="md:col-span-2 flex flex-wrap gap-4 text-sm text-[#BFD2E6]">
                            <label class="inline-flex items-center gap-2">
                                <input name="is_required" type="checkbox" value="1" checked class="rounded border-[rgba(0,209,255,0.2)] bg-[#050816]" />
                                Étape requise
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input name="allow_skip" type="checkbox" value="1" class="rounded border-[rgba(0,209,255,0.2)] bg-[#050816]" />
                                Autoriser le skip
                            </label>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="dgcpt-btn-primary">Ajouter l’étape</button>
                        </div>
                    </form>
                </div>

                <div class="dgcpt-surface p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-[#E6EEF8]">Transitions</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($template->transitions as $transition)
                            <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] px-4 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="text-sm text-[#E6EEF8]">
                                        <span class="font-semibold">{{ $transition->fromStage?->name ?? '—' }}</span>
                                        <span class="mx-2 text-[#73D8FF]">→</span>
                                        <span class="font-semibold">{{ $transition->toStage?->name ?? '—' }}</span>
                                        @if ($transition->is_automatic)
                                            <span class="ml-3 rounded-full bg-[#123D2C] px-2 py-0.5 text-[11px] font-semibold text-[#7EF2BE]">Auto</span>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('workflow-builder.transitions.destroy', $transition) }}" onsubmit="return confirm('Supprimer cette transition ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-semibold text-[#FF9B9B] hover:underline">Supprimer</button>
                                    </form>
                                </div>
                                @if ($transition->condition_type || $transition->role_required)
                                    <p class="mt-2 text-xs text-[#9FB3C8]">
                                        Condition: {{ $transition->condition_type ?: '—' }} · Rôle: {{ $transition->role_required ?: '—' }}
                                    </p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-[#9FB3C8]">Aucune transition définie.</p>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('workflow-builder.transitions.store', $template) }}" class="mt-6 grid gap-4 md:grid-cols-2">
                        @csrf
                        <div>
                            <label class="dgcpt-label">Depuis</label>
                            <select name="from_stage_id" class="dgcpt-input">
                                @foreach ($template->stages->sortBy('sort_order') as $stage)
                                    <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="dgcpt-label">Vers</label>
                            <select name="to_stage_id" class="dgcpt-input">
                                @foreach ($template->stages->sortBy('sort_order') as $stage)
                                    <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="dgcpt-label">Condition type</label>
                            <input name="condition_type" type="text" class="dgcpt-input" placeholder="mission_status / metadata_value / ..." />
                        </div>
                        <div>
                            <label class="dgcpt-label">Rôle requis</label>
                            <input name="role_required" type="text" class="dgcpt-input" placeholder="institutional:copri" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="dgcpt-label">Condition JSON</label>
                            <textarea name="condition_configuration_text" rows="4" class="dgcpt-textarea font-mono text-xs" placeholder='{"status":"validée_IS"}'></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="inline-flex items-center gap-2 text-sm text-[#BFD2E6]">
                                <input name="is_automatic" type="checkbox" value="1" class="rounded border-[rgba(0,209,255,0.2)] bg-[#050816]" />
                                Transition automatique
                            </label>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="dgcpt-btn-primary">Ajouter la transition</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-6">
                <div class="dgcpt-surface p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-[#E6EEF8]">Sidebar configuration</h2>
                    @if ($selectedStage)
                        <p class="mt-1 text-sm text-[#9FB3C8]">Étape sélectionnée: <span class="font-semibold text-[#73D8FF]">{{ $selectedStage->name }}</span></p>

                        <form method="POST" action="{{ route('workflow-builder.stages.update', $selectedStage) }}" class="mt-4 space-y-4" id="selected-stage-form" data-stage-id="{{ $selectedStage->id }}">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="dgcpt-label">Nom</label>
                                <input name="name" type="text" value="{{ old('name', $selectedStage->name) }}" required class="dgcpt-input" />
                            </div>
                            <div>
                                <label class="dgcpt-label">Code</label>
                                <input name="code" type="text" value="{{ old('code', $selectedStage->code) }}" required class="dgcpt-input font-mono text-sm" />
                            </div>
                            <div>
                                <label class="dgcpt-label">Type</label>
                                <select name="stage_type" class="dgcpt-input">
                                    @foreach ($stageTypeLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('stage_type', $selectedStage->resolvedStageType()?->value) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="dgcpt-label">UI component</label>
                                <input name="ui_component" type="text" value="{{ old('ui_component', $selectedStage->ui_component) }}" class="dgcpt-input" />
                            </div>
                            <div>
                                <label class="dgcpt-label">Mode d’exécution</label>
                                <select name="execution_mode" class="dgcpt-input">
                                    <option value="">Déduire du type</option>
                                    @foreach ($executionModeLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('execution_mode', $selectedStage->resolvedExecutionMode()?->value) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="dgcpt-label">Questionnaire</label>
                                <select name="questionnaire_template_id" class="dgcpt-input">
                                    <option value="">Aucun</option>
                                    @foreach ($questionnaireTemplates as $questionnaire)
                                        <option value="{{ $questionnaire->id }}" @selected((string) old('questionnaire_template_id', $selectedStage->questionnaire_template_id) === (string) $questionnaire->id)>
                                            {{ $questionnaire->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="dgcpt-label">Formulaire</label>
                                <select name="form_template_id" class="dgcpt-input">
                                    <option value="">Aucun</option>
                                    @foreach ($formTemplates as $formTemplate)
                                        <option value="{{ $formTemplate->id }}" @selected((string) old('form_template_id', $selectedStage->form_template_id) === (string) $formTemplate->id)>
                                            {{ $formTemplate->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="dgcpt-label">Component key</label>
                                <input name="component_key" type="text" value="{{ old('component_key', $selectedStage->resolvedComponentKey()) }}" class="dgcpt-input font-mono text-sm" />
                            </div>
                            <div>
                                <label class="dgcpt-label">Approval role</label>
                                <select name="approval_role_id" class="dgcpt-input">
                                    <option value="">Aucun</option>
                                    @foreach ($roleOptions as $role)
                                        <option value="{{ $role->id }}" @selected((string) old('approval_role_id', $selectedStage->approval_role_id) === (string) $role->id)>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="dgcpt-label">X</label>
                                    <input id="selected-position-x" name="position_x" type="number" value="{{ old('position_x', $selectedStage->position_x ?? 0) }}" class="dgcpt-input" />
                                </div>
                                <div>
                                    <label class="dgcpt-label">Y</label>
                                    <input id="selected-position-y" name="position_y" type="number" value="{{ old('position_y', $selectedStage->position_y ?? 0) }}" class="dgcpt-input" />
                                </div>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="dgcpt-label">Couleur</label>
                                    <input name="color" type="text" value="{{ old('color', $selectedStage->color) }}" class="dgcpt-input" placeholder="#0A2A66" />
                                </div>
                                <div>
                                    <label class="dgcpt-label">Icône</label>
                                    <input name="icon" type="text" value="{{ old('icon', $selectedStage->icon) }}" class="dgcpt-input" placeholder="workflow" />
                                </div>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="dgcpt-label">Ordre</label>
                                    <input name="sort_order" type="number" value="{{ old('sort_order', $selectedStage->sort_order) }}" class="dgcpt-input" />
                                </div>
                                <div>
                                    <label class="dgcpt-label">Role scope</label>
                                    <input name="role_scope" type="text" value="{{ old('role_scope', $selectedStage->role_scope) }}" class="dgcpt-input" placeholder="inspecteur_services" />
                                </div>
                            </div>
                            <div class="space-y-2 text-sm text-[#BFD2E6]">
                                <label class="inline-flex items-center gap-2">
                                    <input name="is_required" type="checkbox" value="1" @checked(old('is_required', $selectedStage->is_required)) class="rounded border-[rgba(0,209,255,0.2)] bg-[#050816]" />
                                    Étape requise
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input name="is_repeatable" type="checkbox" value="1" @checked(old('is_repeatable', $selectedStage->is_repeatable)) class="rounded border-[rgba(0,209,255,0.2)] bg-[#050816]" />
                                    Étape répétable
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input name="allow_skip" type="checkbox" value="1" @checked(old('allow_skip', $selectedStage->allow_skip)) class="rounded border-[rgba(0,209,255,0.2)] bg-[#050816]" />
                                    Skip autorisé
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input name="requires_approval" type="checkbox" value="1" @checked(old('requires_approval', $selectedStage->requires_approval)) class="rounded border-[rgba(0,209,255,0.2)] bg-[#050816]" />
                                    Nécessite approbation
                                </label>
                            </div>
                            <div>
                                <label class="dgcpt-label">Configuration JSON</label>
                                <textarea name="configuration_json_text" rows="5" class="dgcpt-textarea font-mono text-xs">{{ old('configuration_json_text', json_encode($selectedStage->resolvedConfiguration(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea>
                            </div>
                            <div>
                                <label class="dgcpt-label">Validation rules JSON</label>
                                <textarea name="validation_rules_json_text" rows="5" class="dgcpt-textarea font-mono text-xs">{{ old('validation_rules_json_text', json_encode($selectedStage->resolvedValidationRules(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea>
                            </div>
                            <div>
                                <label class="dgcpt-label">Form schema JSON</label>
                                <textarea name="form_schema_json_text" rows="5" class="dgcpt-textarea font-mono text-xs">{{ old('form_schema_json_text', json_encode($selectedStage->resolvedFormSchema(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea>
                            </div>
                            <div>
                                <label class="dgcpt-label">Risk matrix JSON</label>
                                <textarea name="risk_matrix_schema_json_text" rows="5" class="dgcpt-textarea font-mono text-xs">{{ old('risk_matrix_schema_json_text', json_encode($selectedStage->resolvedRiskMatrixSchema(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea>
                            </div>
                            <div>
                                <label class="dgcpt-label">Description</label>
                                <textarea name="description" rows="4" class="dgcpt-textarea">{{ old('description', $selectedStage->description) }}</textarea>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <button type="submit" class="dgcpt-btn-primary">Enregistrer l’étape</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('workflow-builder.stages.destroy', $selectedStage) }}" class="mt-3" onsubmit="return confirm('Supprimer cette étape ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg border border-[rgba(255,90,90,0.28)] px-4 py-2 text-sm font-semibold text-[#FF9B9B] hover:bg-[rgba(255,90,90,0.10)]">
                                Supprimer l’étape
                            </button>
                        </form>
                    @else
                        <p class="mt-3 text-sm text-[#9FB3C8]">Sélectionnez une étape depuis le canvas pour éditer sa configuration détaillée.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('workflow-canvas');
            if (!canvas) {
                return;
            }

            let dragging = null;
            let offsetX = 0;
            let offsetY = 0;

            const stageForm = document.getElementById('selected-stage-form');
            const selectedStageId = stageForm?.dataset.stageId ?? null;
            const posXInput = document.getElementById('selected-position-x');
            const posYInput = document.getElementById('selected-position-y');

            canvas.querySelectorAll('.workflow-stage-card').forEach((card) => {
                card.addEventListener('dragstart', (event) => {
                    dragging = card;
                    const rect = card.getBoundingClientRect();
                    offsetX = event.clientX - rect.left;
                    offsetY = event.clientY - rect.top;
                });
            });

            canvas.addEventListener('dragover', (event) => {
                event.preventDefault();
            });

            canvas.addEventListener('drop', (event) => {
                event.preventDefault();

                if (!dragging) {
                    return;
                }

                const canvasRect = canvas.getBoundingClientRect();
                const left = Math.max(0, Math.round(event.clientX - canvasRect.left - offsetX));
                const top = Math.max(0, Math.round(event.clientY - canvasRect.top - offsetY));

                dragging.style.left = `${left}px`;
                dragging.style.top = `${top}px`;

                const label = dragging.querySelector('[data-position-label]');
                if (label) {
                    label.textContent = `${left}, ${top}`;
                }

                if (selectedStageId && dragging.dataset.stageId === selectedStageId) {
                    if (posXInput) posXInput.value = left;
                    if (posYInput) posYInput.value = top;
                }

                dragging = null;
            });
        });
    </script>
</x-app-layout>
