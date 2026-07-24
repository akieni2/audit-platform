<x-app-layout>
    <style>
        .org-builder { display:grid; grid-template-columns:260px minmax(0,1fr); gap:1.25rem; }
        .org-palette { position:sticky; top:1rem; align-self:start; max-height:calc(100vh - 2rem); overflow:auto; }
        .org-tool { cursor:grab; user-select:none; transition:.15s ease; }
        .org-tool:hover { transform:translateY(-1px); border-color:rgba(0,209,255,.55); }
        .org-tool:active { cursor:grabbing; }
        .org-tool.org-tool--selected { border-color:#00D1FF; background:rgba(0,209,255,.16); box-shadow:0 0 0 2px rgba(0,209,255,.16); }
        .org-canvas { min-height:560px; overflow:auto; }
        .org-root-zone { min-width:760px; }
        .org-drop-active { outline:2px dashed #00D1FF !important; outline-offset:3px; background:rgba(0,209,255,.09) !important; }
        .org-children { position:relative; margin-left:1.8rem; padding-left:1.5rem; border-left:2px solid rgba(0,209,255,.22); }
        .org-children > .org-node-wrap::before { content:""; position:absolute; left:-1.5rem; top:2rem; width:1.5rem; border-top:2px solid rgba(0,209,255,.22); }
        .org-node-wrap { position:relative; }
        @media (max-width:900px) { .org-builder { grid-template-columns:1fr; } .org-palette { position:static; max-height:none; } }
        @media (max-width:640px) {
            .org-canvas { min-height:420px; padding:1rem; }
            .org-root-zone { min-width:0; padding:.65rem; }
            .org-children { margin-left:.5rem; padding-left:.7rem; }
            .org-children > .org-node-wrap::before { left:-.7rem; width:.7rem; }
        }
    </style>

    <div class="mx-auto max-w-[1600px] space-y-6 px-0 py-2">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="dgcpt-card-title">{{ $isGlobalOrganigramme ? 'Organigramme institutionnel global' : 'Organigramme fonctionnel du département' }}</p>
                <h1 class="dgcpt-page-title">{{ $isGlobalOrganigramme ? 'Organisation institutionnelle dynamique' : auth()->user()?->department?->name }}</h1>
                <p class="mt-1 text-sm dgcpt-text-muted">Glissez une structure ou une fonction sur le niveau cible. Les liens hiérarchiques sont reconstruits automatiquement.</p>
            </div>
            <a href="{{ route('admin.departments.index') }}" class="dgcpt-btn-outline">Retour structures</a>
        </div>

        @if (! $canBuildOrganigramme)
            <div class="dgcpt-surface border-[#FFB020]/30 p-4 text-sm text-[#BFD2E6]">Consultation de l’organigramme fonctionnel de votre département. Son responsable est habilité à le construire.</div>
        @endif

        <div class="org-builder">
            <aside class="org-palette dgcpt-surface space-y-6 p-4">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wider text-[#E6EEF8]">Objets administratifs</p>
                    <p class="mt-1 text-xs text-[#9FB3C8]">Déposez un objet sur sa future structure parente.</p>
                </div>
                <div class="grid gap-2">
                    @foreach ($structureTypes as $value => $label)
                        <div class="org-tool rounded-lg border border-[rgba(0,209,255,.18)] bg-[rgba(0,209,255,.05)] px-3 py-2 text-sm font-semibold text-[#73D8FF]"
                             role="button" tabindex="0"
                             draggable="{{ $canBuildOrganigramme ? 'true' : 'false' }}"
                             data-org-payload='@json(["kind" => "new-structure", "type" => $value, "label" => $label])'>
                            <span class="mr-2 text-[#00D1FF]">◇</span>{{ $label }}
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-[rgba(0,209,255,.14)] pt-5">
                    <p class="text-sm font-bold uppercase tracking-wider text-[#E6EEF8]">Fonctions administratives</p>
                    <p class="mt-1 text-xs text-[#9FB3C8]">Déposez une fonction sur une structure.</p>
                </div>
                <div class="grid gap-2">
                    @foreach ($positionTypes as $value => $label)
                        <div class="org-tool rounded-lg border border-[rgba(124,92,255,.24)] bg-[rgba(124,92,255,.08)] px-3 py-2 text-sm text-[#C8BCFF]"
                             role="button" tabindex="0"
                             draggable="{{ $canBuildOrganigramme ? 'true' : 'false' }}"
                             data-org-payload='@json(["kind" => "position", "position" => $value])'>
                            <span class="mr-2">●</span>{{ $label }}
                        </div>
                    @endforeach
                </div>
            </aside>

            <main class="org-canvas dgcpt-surface p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-lg font-bold text-[#E6EEF8]">Canevas hiérarchique</p>
                        <p class="text-xs text-[#9FB3C8]">Vous pouvez aussi déplacer une structure existante sur une nouvelle parente.</p>
                    </div>
                    <span id="org-save-state" class="text-xs font-semibold text-[#73D8FF]"></span>
                </div>

                <div class="org-root-zone space-y-4 rounded-xl border border-dashed border-[rgba(0,209,255,.18)] p-4"
                     data-org-drop-root="true">
                    @forelse ($departmentTree as $root)
                        @include('iam.admin.departments.partials.org-node', ['department' => $root, 'level' => 0, 'builder' => $canBuildOrganigramme])
                    @empty
                        <div class="py-20 text-center text-sm text-[#9FB3C8]">Glissez ici la première structure de l’organigramme.</div>
                    @endforelse
                </div>
            </main>
        </div>
    </div>

    <dialog id="org-create-dialog" class="w-full max-w-lg rounded-xl border border-[rgba(0,209,255,.3)] bg-[#071220] p-0 text-[#E6EEF8] shadow-2xl backdrop:bg-black/70">
        <form id="org-create-form" class="space-y-5 p-6">
            <div>
                <p class="dgcpt-card-title">Nouvel objet administratif</p>
                <h2 id="org-dialog-title" class="mt-1 text-xl font-bold"></h2>
                <p id="org-dialog-parent" class="mt-1 text-xs text-[#9FB3C8]"></p>
            </div>
            <input type="hidden" name="type">
            <input type="hidden" name="parent_department_id">
            <div><label class="dgcpt-label">Nom</label><input class="dgcpt-input" name="name" required maxlength="255"></div>
            <div><label class="dgcpt-label">Code</label><input class="dgcpt-input" name="code" required maxlength="32" placeholder="Ex. DSI, PI, PMAR"></div>
            <div>
                <label class="dgcpt-label">Référentiel d’audit</label>
                <select class="dgcpt-select" name="default_methodology_template_id">
                    <option value="">Aucun / non applicable</option>
                    @foreach ($methodologies as $methodology)<option value="{{ $methodology->id }}">{{ $methodology->name }}</option>@endforeach
                </select>
            </div>
            <p id="org-form-error" class="hidden rounded-lg bg-red-950/40 p-3 text-sm text-red-300"></p>
            <div class="flex justify-end gap-3">
                <button type="button" class="dgcpt-btn-outline" data-close-dialog>Annuler</button>
                <button type="submit" class="dgcpt-btn-primary">Créer et rattacher</button>
            </div>
        </form>
    </dialog>

    <dialog id="org-supervisor-dialog" class="w-full max-w-lg rounded-xl border border-[rgba(0,209,255,.3)] bg-[#071220] p-0 text-[#E6EEF8] shadow-2xl backdrop:bg-black/70">
        <form id="org-supervisor-form" class="space-y-5 p-6">
            <div>
                <p class="dgcpt-card-title">Responsable hiérarchique</p>
                <h2 id="org-supervisor-title" class="mt-1 text-xl font-bold"></h2>
                <p class="mt-1 text-xs text-[#9FB3C8]">Choisissez un utilisateur actif déjà enregistré dans votre périmètre.</p>
            </div>
            <input type="hidden" name="department_id">
            <div>
                <label class="dgcpt-label">Titulaire</label>
                <select name="supervisor_user_id" class="dgcpt-select">
                    <option value="">Aucun responsable</option>
                    @foreach ($supervisorCandidates as $candidate)
                        <option value="{{ $candidate->id }}">{{ $candidate->displayName() }} — {{ $candidate->department?->code ?? 'Sans structure' }} — {{ $candidate->position ?: $candidate->email }}</option>
                    @endforeach
                </select>
            </div>
            <p id="org-supervisor-error" class="hidden rounded-lg bg-red-950/40 p-3 text-sm text-red-300"></p>
            <div class="flex justify-end gap-3">
                <button type="button" class="dgcpt-btn-outline" data-close-supervisor-dialog>Annuler</button>
                <button type="submit" class="dgcpt-btn-primary">Enregistrer le responsable</button>
            </div>
        </form>
    </dialog>

    <script>
    (() => {
        const canBuild = @json($canBuildOrganigramme);
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const state = document.getElementById('org-save-state');
        const dialog = document.getElementById('org-create-dialog');
        const form = document.getElementById('org-create-form');
        const supervisorDialog = document.getElementById('org-supervisor-dialog');
        const supervisorForm = document.getElementById('org-supervisor-form');
        let dragged = null;

        document.querySelectorAll('[data-org-payload]').forEach(el => {
            const selectTool = () => {
                if (!canBuild) return;
                document.querySelectorAll('[data-org-payload]').forEach(tool => tool.classList.remove('org-tool--selected'));
                dragged = JSON.parse(el.dataset.orgPayload);
                el.classList.add('org-tool--selected');
                state.textContent = 'Objet sélectionné : touchez maintenant la structure cible.';
            };
            el.addEventListener('click', selectTool);
            el.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    selectTool();
                }
            });
            el.addEventListener('dragstart', event => {
                dragged = JSON.parse(el.dataset.orgPayload);
                event.dataTransfer.effectAllowed = 'copy';
                event.dataTransfer.setData('application/json', JSON.stringify(dragged));
            });
        });
        document.querySelectorAll('[data-org-existing]').forEach(el => {
            el.addEventListener('dragstart', event => {
                event.stopPropagation();
                dragged = {kind:'existing', id:Number(el.dataset.orgExisting)};
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('application/json', JSON.stringify(dragged));
            });
        });
        document.querySelectorAll('[data-org-delete]').forEach(button => {
            button.addEventListener('click', async event => {
                event.preventDefault(); event.stopPropagation();
                const code = button.dataset.orgDeleteCode;
                const confirmation = window.prompt(`Suppression définitive de cette structure et de ses sous-structures. Saisissez ${code} pour confirmer.`);
                if (confirmation === null) return;
                await send(`{{ url('/admin/departments') }}/${button.dataset.orgDelete}`, 'DELETE', {confirmation_code:confirmation});
            });
            button.addEventListener('dragstart', event => event.preventDefault());
        });
        document.querySelectorAll('[data-org-edit-supervisor]').forEach(button => {
            button.addEventListener('click', event => {
                event.preventDefault(); event.stopPropagation();
                supervisorForm.reset();
                supervisorForm.elements.department_id.value = button.dataset.orgEditSupervisor;
                supervisorForm.elements.supervisor_user_id.value = button.dataset.orgCurrentSupervisor || '';
                document.getElementById('org-supervisor-title').textContent = button.dataset.orgEditName;
                document.getElementById('org-supervisor-error').classList.add('hidden');
                supervisorDialog.showModal();
            });
            button.addEventListener('dragstart', event => event.preventDefault());
        });

        document.querySelectorAll('[data-org-drop-target], [data-org-drop-root]').forEach(zone => {
            zone.addEventListener('click', async event => {
                if (!dragged || !canBuild || event.target.closest('button, a, form')) return;
                event.preventDefault();
                event.stopPropagation();
                const payload = dragged;
                const parentId = zone.dataset.orgDropTarget ? Number(zone.dataset.orgDropTarget) : null;
                const parentName = zone.dataset.orgDropName || 'Racine de l’organigramme';
                dragged = null;
                document.querySelectorAll('[data-org-payload]').forEach(tool => tool.classList.remove('org-tool--selected'));
                if (payload.kind === 'new-structure') return openCreate(payload, parentId, parentName);
                if (payload.kind === 'position' && parentId) return send(`{{ url('/admin/departments') }}/${parentId}/organigramme/position`, 'PATCH', {position_title:payload.position});
                state.textContent = 'Sélectionnez une structure cible.';
            });
            zone.addEventListener('dragover', event => { event.preventDefault(); event.stopPropagation(); zone.classList.add('org-drop-active'); });
            zone.addEventListener('dragleave', event => { event.stopPropagation(); zone.classList.remove('org-drop-active'); });
            zone.addEventListener('drop', async event => {
                event.preventDefault(); event.stopPropagation(); zone.classList.remove('org-drop-active');
                const payload = dragged || JSON.parse(event.dataTransfer.getData('application/json') || '{}');
                const parentId = zone.dataset.orgDropTarget ? Number(zone.dataset.orgDropTarget) : null;
                const parentName = zone.dataset.orgDropName || 'Racine de l’organigramme';
                if (payload.kind === 'new-structure' && canBuild) return openCreate(payload, parentId, parentName);
                if (payload.kind === 'existing' && canBuild) return send(`{{ url('/admin/departments') }}/${payload.id}/organigramme/move`, 'PATCH', {parent_department_id:parentId});
                if (payload.kind === 'position' && parentId) return send(`{{ url('/admin/departments') }}/${parentId}/organigramme/position`, 'PATCH', {position_title:payload.position});
            });
        });

        function openCreate(payload, parentId, parentName) {
            form.reset();
            form.elements.type.value = payload.type;
            form.elements.parent_department_id.value = parentId || '';
            document.getElementById('org-dialog-title').textContent = payload.label;
            document.getElementById('org-dialog-parent').textContent = `Rattachement : ${parentName}`;
            document.getElementById('org-form-error').classList.add('hidden');
            dialog.showModal();
        }
        document.querySelector('[data-close-dialog]').addEventListener('click', () => dialog.close());
        document.querySelector('[data-close-supervisor-dialog]').addEventListener('click', () => supervisorDialog.close());
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const data = Object.fromEntries(new FormData(form).entries());
            data.parent_department_id = data.parent_department_id ? Number(data.parent_department_id) : null;
            data.default_methodology_template_id = data.default_methodology_template_id ? Number(data.default_methodology_template_id) : null;
            await send(`{{ route('admin.departments.visual-store') }}`, 'POST', data, true);
        });
        supervisorForm.addEventListener('submit', async event => {
            event.preventDefault();
            const departmentId = supervisorForm.elements.department_id.value;
            const supervisorId = supervisorForm.elements.supervisor_user_id.value;
            await send(`{{ url('/admin/departments') }}/${departmentId}/organigramme/supervisor`, 'PATCH', {
                supervisor_user_id: supervisorId ? Number(supervisorId) : null,
            }, false, supervisorDialog);
        });

        async function send(url, method, data, closeDialog = false, dialogToClose = dialog) {
            state.textContent = 'Enregistrement…';
            try {
                const response = await fetch(url, {method, headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token}, body:JSON.stringify(data)});
                const body = await response.json();
                if (!response.ok) throw new Error(body.message || Object.values(body.errors || {})[0]?.[0] || 'Opération refusée.');
                state.textContent = body.message || 'Enregistré';
                if (closeDialog) dialogToClose.close();
                if (dialogToClose === supervisorDialog) supervisorDialog.close();
                window.setTimeout(() => window.location.reload(), 350);
            } catch (error) {
                state.textContent = 'Erreur';
                const target = document.getElementById('org-form-error');
                if (supervisorDialog.open) {
                    const supervisorError = document.getElementById('org-supervisor-error');
                    supervisorError.textContent = error.message;
                    supervisorError.classList.remove('hidden');
                } else if (dialog.open) { target.textContent = error.message; target.classList.remove('hidden'); }
                else alert(error.message);
            }
        }
    })();
    </script>
</x-app-layout>
