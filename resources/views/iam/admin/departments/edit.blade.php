@php
    $profile = $department->intelligence_profile ?? [];
    $topManager = data_get($profile, 'top_manager_profile', []);
    $activities = implode("\n", data_get($profile, 'position_activities', []));
@endphp

<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">
        <div>
            <p class="dgcpt-card-title">Organigramme</p>
            <h1 class="dgcpt-page-title">Modifier {{ $department->code }}</h1>
            <p class="mt-1 text-sm"><a href="{{ route('admin.departments.index') }}" class="dgcpt-link">← Retour liste</a></p>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-[rgba(0,168,107,0.4)] bg-[#10192B] px-4 py-3 text-sm font-medium text-[#E6EEF8]">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-[rgba(255,90,90,0.45)] bg-[#10192B] px-4 py-3 text-sm text-[#FF5A5A]">
                <ul class="ms-5 list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('admin.departments.update', $department) }}" class="dgcpt-surface space-y-8 p-6 shadow-sm">
            @csrf
            @method('patch')

            <div class="rounded-lg border border-[rgba(0,209,255,0.18)] bg-[rgba(0,209,255,0.05)] p-4 text-sm text-[#BFD2E6]">
                <p class="font-semibold text-[#E6EEF8]">Position dans l’organigramme</p>
                <p class="mt-1">Le type et la structure parente déterminent le niveau hiérarchique. Le responsable affecté supervise cette structure et peut gouverner ses missions selon ses habilitations.</p>
            </div>

            <section class="space-y-4">
                <div>
                    <p class="text-lg font-bold text-[#E6EEF8]">Identité et rattachement</p>
                    <p class="text-sm text-[#9FB3C8]">Positionnez la structure dans l’organigramme commun.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="dgcpt-label">Code <span class="text-[#FF5A5A]">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $department->code) }}" required maxlength="32" class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Type</label>
                        <select name="type" class="dgcpt-select">
                            @foreach ($structureTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $department->type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="dgcpt-label">Structure parente</label>
                        <select name="parent_department_id" class="dgcpt-select">
                            <option value="">Sommet / Direction générale</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}" @selected(old('parent_department_id', $department->parent_department_id) == $d->id)>{{ $d->code }} — {{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="dgcpt-label">Nom <span class="text-[#FF5A5A]">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $department->name) }}" required class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label">Description</label>
                    <textarea name="description" rows="3" class="dgcpt-textarea">{{ old('description', $department->description) }}</textarea>
                </div>
            </section>

            <section class="space-y-4">
                <div>
                    <p class="text-lg font-bold text-[#E6EEF8]">Référentiels et gouvernance</p>
                    <p class="text-sm text-[#9FB3C8]">Choix par défaut pour les missions de cette structure.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="dgcpt-label">Référentiel par défaut</label>
                        <select name="default_methodology_template_id" class="dgcpt-select">
                            <option value="">Aucun</option>
                            @foreach ($methodologies as $methodology)
                                <option value="{{ $methodology->id }}" @selected(old('default_methodology_template_id', $department->default_methodology_template_id) == $methodology->id)>{{ $methodology->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="dgcpt-label">Taxonomie par défaut</label>
                        <select name="default_taxonomy_id" class="dgcpt-select">
                            <option value="">Taxonomie nationale</option>
                            @foreach ($taxonomies as $taxonomy)
                                <option value="{{ $taxonomy->id }}" @selected(old('default_taxonomy_id', $department->default_taxonomy_id) == $taxonomy->id)>{{ $taxonomy->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="dgcpt-label">Périmètre de gouvernance</label>
                        <input type="text" name="governance_scope" value="{{ old('governance_scope', $department->governance_scope) }}" class="dgcpt-input" />
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <div>
                    <p class="text-lg font-bold text-[#E6EEF8]">Poste, activités et responsable hiérarchique</p>
                    <p class="text-sm text-[#9FB3C8]">Décrivez le poste principal et rattachez le responsable de la structure.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="dgcpt-label">Appellation du poste principal</label>
                        <input type="text" name="position_title" value="{{ old('position_title', data_get($profile, 'position_title')) }}" class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Superviseur référent existant</label>
                        <select name="supervisor_user_id" class="dgcpt-select">
                            <option value="">Aucun</option>
                            @foreach ($supervisors as $s)
                                <option value="{{ $s->id }}" @selected(old('supervisor_user_id', $department->supervisor_user_id) == $s->id)>{{ $s->displayName() }} ({{ $s->email }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="dgcpt-label">Description du poste</label>
                    <textarea name="position_description" rows="3" class="dgcpt-textarea">{{ old('position_description', data_get($profile, 'position_description')) }}</textarea>
                </div>
                <div>
                    <label class="dgcpt-label">Activités principales</label>
                    <textarea name="position_activities" rows="4" class="dgcpt-textarea">{{ old('position_activities', $activities) }}</textarea>
                </div>

                <div class="rounded-lg border border-[rgba(0,209,255,0.14)] bg-[rgba(7,18,32,0.72)] p-4">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-[#E6EEF8]">
                        <input type="checkbox" name="create_top_manager" value="1" class="h-4 w-4 rounded border-[rgba(0,209,255,0.35)] bg-[#050816] text-[#00D1FF] focus:ring-[#00D1FF]" @checked(old('create_top_manager')) />
                        Créer un nouveau compte de responsable hiérarchique
                    </label>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="dgcpt-label">Appellation</label>
                            <input type="text" name="top_manager_title" value="{{ old('top_manager_title', data_get($topManager, 'title')) }}" class="dgcpt-input" />
                        </div>
                        <div>
                            <label class="dgcpt-label">Rôle système</label>
                            <select name="top_manager_role_id" class="dgcpt-select">
                                <option value="">Rôle le plus élevé disponible</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected(old('top_manager_role_id', data_get($topManager, 'role_id')) == $role->id)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="dgcpt-label">Nom du titulaire</label>
                            <input type="text" name="top_manager_name" value="{{ old('top_manager_name', data_get($topManager, 'name')) }}" class="dgcpt-input" />
                        </div>
                        <div>
                            <label class="dgcpt-label">Email du titulaire</label>
                            <input type="email" name="top_manager_email" value="{{ old('top_manager_email') }}" class="dgcpt-input" />
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="dgcpt-label">Couleur UI</label>
                    <input type="text" name="accent_color" value="{{ old('accent_color', $department->accent_color) }}" class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label">Logo</label>
                    <input type="text" name="logo_path" value="{{ old('logo_path', $department->logo_path) }}" class="dgcpt-input" />
                </div>
            </div>

            <div class="flex flex-wrap gap-5">
                <input type="hidden" name="active" value="0" />
                <label class="inline-flex items-center gap-2 text-sm font-medium text-[#9FB3C8]">
                    <input type="checkbox" name="active" value="1" class="h-4 w-4 rounded border-[rgba(0,209,255,0.35)] bg-[#050816] text-[#00D1FF] focus:ring-[#00D1FF]" @checked(old('active', $department->active)) />
                    Structure active
                </label>
                <input type="hidden" name="executive_visibility" value="0" />
                <label class="inline-flex items-center gap-2 text-sm font-medium text-[#9FB3C8]">
                    <input type="checkbox" name="executive_visibility" value="1" class="h-4 w-4 rounded border-[rgba(0,209,255,0.35)] bg-[#050816] text-[#00D1FF] focus:ring-[#00D1FF]" @checked(old('executive_visibility', $department->executive_visibility)) />
                    Visible dans les vues top management
                </label>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="dgcpt-btn-primary">Enregistrer</button>
                <a href="{{ route('admin.departments.index') }}" class="dgcpt-btn-outline">Annuler</a>
            </div>
        </form>

        @if ($department->active)
            <div class="rounded-xl border border-[rgba(255,90,90,0.45)] bg-[#10192B] p-4">
                <p class="mb-2 text-sm font-semibold text-[#FF5A5A]">Suppression de l’organigramme actif</p>
                <form method="post" action="{{ route('admin.departments.destroy', $department) }}" onsubmit="return confirm('Désactiver cette structure ? Les rattachements existants restent en base.');">
                    @csrf
                    @method('delete')
                    <button type="submit" class="dgcpt-btn-outline border-[rgba(255,90,90,0.45)] text-[#FF5A5A] hover:border-[#FF5A5A] hover:bg-[#122038]">
                        Supprimer / désactiver la structure
                    </button>
                    <p class="mt-2 text-xs text-[#9FB3C8]">La structure est retirée des vues actives sans effacer les historiques d’audit, utilisateurs et missions.</p>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
