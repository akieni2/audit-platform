<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">
        <div>
            <p class="dgcpt-card-title">Organigramme</p>
            <h1 class="dgcpt-page-title">Nouvelle structure</h1>
            <p class="mt-1 text-sm"><a href="{{ route('admin.departments.index') }}" class="dgcpt-link">← Retour liste</a></p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-[rgba(255,90,90,0.45)] bg-[#10192B] px-4 py-3 text-sm text-[#FF5A5A]">
                <ul class="ms-5 list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('admin.departments.store') }}" class="dgcpt-surface space-y-8 p-6 shadow-sm">
            @csrf

            <div class="rounded-lg border border-[rgba(0,209,255,0.18)] bg-[rgba(0,209,255,0.05)] p-4 text-sm text-[#BFD2E6]">
                <p class="font-semibold text-[#E6EEF8]">Chaîne organisationnelle</p>
                <p class="mt-1">Créez d’abord une Direction ou l’Inspection des Services, puis rattachez ses pôles ou sous-directions, et enfin les services. Le responsable affecté à chaque structure devient son superviseur institutionnel.</p>
                <p class="mt-2 text-xs text-[#73D8FF]">Exemple : Inspection des Services → PI / PMAR / PCPC → Inspecteurs vérificateurs. DSI → services → agents opérationnels.</p>
            </div>

            <section class="space-y-4">
                <div>
                    <p class="text-lg font-bold text-[#E6EEF8]">Identité et rattachement</p>
                    <p class="text-sm text-[#9FB3C8]">Placez la structure dans l’organigramme commun.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="dgcpt-label">Code <span class="text-[#FF5A5A]">*</span></label>
                        <input type="text" name="code" value="{{ old('code') }}" required maxlength="32" class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Type</label>
                        <select name="type" class="dgcpt-select">
                            @foreach ($structureTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="dgcpt-label">Structure parente</label>
                        <select name="parent_department_id" class="dgcpt-select">
                            <option value="">Sommet / Direction générale</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}" @selected(old('parent_department_id') == $d->id)>{{ $d->code }} — {{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="dgcpt-label">Nom <span class="text-[#FF5A5A]">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label">Description de la structure</label>
                    <textarea name="description" rows="3" class="dgcpt-textarea">{{ old('description') }}</textarea>
                </div>
            </section>

            <section class="space-y-4">
                <div>
                    <p class="text-lg font-bold text-[#E6EEF8]">Référentiels et gouvernance</p>
                    <p class="text-sm text-[#9FB3C8]">Définissez le référentiel par défaut et la visibilité exécutive.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="dgcpt-label">Référentiel par défaut</label>
                        <select name="default_methodology_template_id" class="dgcpt-select">
                            <option value="">Aucun</option>
                            @foreach ($methodologies as $methodology)
                                <option value="{{ $methodology->id }}" @selected(old('default_methodology_template_id') == $methodology->id)>{{ $methodology->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="dgcpt-label">Taxonomie par défaut</label>
                        <select name="default_taxonomy_id" class="dgcpt-select">
                            <option value="">Taxonomie nationale</option>
                            @foreach ($taxonomies as $taxonomy)
                                <option value="{{ $taxonomy->id }}" @selected(old('default_taxonomy_id') == $taxonomy->id)>{{ $taxonomy->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="dgcpt-label">Périmètre de gouvernance</label>
                        <input type="text" name="governance_scope" value="{{ old('governance_scope') }}" placeholder="national, direction, service..." class="dgcpt-input" />
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <div>
                    <p class="text-lg font-bold text-[#E6EEF8]">Poste, activités et responsable hiérarchique</p>
                    <p class="text-sm text-[#9FB3C8]">La structure peut avoir un profil de poste même si le compte utilisateur sera créé plus tard.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="dgcpt-label">Appellation du poste principal</label>
                        <input type="text" name="position_title" value="{{ old('position_title') }}" class="dgcpt-input" placeholder="Directeur, Chef de service, Responsable..." />
                    </div>
                    <div>
                        <label class="dgcpt-label">Superviseur référent existant</label>
                        <select name="supervisor_user_id" class="dgcpt-select">
                            <option value="">Aucun</option>
                            @foreach ($supervisors as $s)
                                <option value="{{ $s->id }}" @selected(old('supervisor_user_id') == $s->id)>{{ $s->displayName() }} ({{ $s->email }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="dgcpt-label">Description du poste</label>
                    <textarea name="position_description" rows="3" class="dgcpt-textarea">{{ old('position_description') }}</textarea>
                </div>
                <div>
                    <label class="dgcpt-label">Activités principales</label>
                    <textarea name="position_activities" rows="4" class="dgcpt-textarea" placeholder="Une activité par ligne">{{ old('position_activities') }}</textarea>
                </div>

                <div class="rounded-lg border border-[rgba(0,209,255,0.14)] bg-[rgba(7,18,32,0.72)] p-4">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-[#E6EEF8]">
                        <input type="checkbox" name="create_top_manager" value="1" class="h-4 w-4 rounded border-[rgba(0,209,255,0.35)] bg-[#050816] text-[#00D1FF] focus:ring-[#00D1FF]" @checked(old('create_top_manager')) />
                        Voulez-vous créer le compte du responsable hiérarchique de cette structure ?
                    </label>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="dgcpt-label">Appellation</label>
                            <input type="text" name="top_manager_title" value="{{ old('top_manager_title') }}" class="dgcpt-input" placeholder="Directeur général, Directeur, Chef de service..." />
                        </div>
                        <div>
                            <label class="dgcpt-label">Rôle système</label>
                            <select name="top_manager_role_id" class="dgcpt-select">
                                <option value="">Rôle le plus élevé disponible</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected(old('top_manager_role_id') == $role->id)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="dgcpt-label">Nom du titulaire</label>
                            <input type="text" name="top_manager_name" value="{{ old('top_manager_name') }}" class="dgcpt-input" />
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
                    <input type="text" name="accent_color" value="{{ old('accent_color') }}" placeholder="#334155 ou slate-700" class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label">Logo</label>
                    <input type="text" name="logo_path" value="{{ old('logo_path') }}" class="dgcpt-input" />
                </div>
            </div>

            <div class="flex flex-wrap gap-5">
                <input type="hidden" name="active" value="0" />
                <label class="inline-flex items-center gap-2 text-sm font-medium text-[#9FB3C8]">
                    <input type="checkbox" name="active" value="1" class="h-4 w-4 rounded border-[rgba(0,209,255,0.35)] bg-[#050816] text-[#00D1FF] focus:ring-[#00D1FF]" @checked(old('active', true)) />
                    Structure active
                </label>
                <input type="hidden" name="executive_visibility" value="0" />
                <label class="inline-flex items-center gap-2 text-sm font-medium text-[#9FB3C8]">
                    <input type="checkbox" name="executive_visibility" value="1" class="h-4 w-4 rounded border-[rgba(0,209,255,0.35)] bg-[#050816] text-[#00D1FF] focus:ring-[#00D1FF]" @checked(old('executive_visibility', true)) />
                    Visible dans les vues top management
                </label>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="dgcpt-btn-primary">Créer</button>
                <a href="{{ route('admin.departments.index') }}" class="dgcpt-btn-outline">Annuler</a>
            </div>
        </form>
    </div>
</x-app-layout>
