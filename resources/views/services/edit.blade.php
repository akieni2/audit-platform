<x-app-layout>
    @php
        /** @var \App\Models\Mission $mission */
        /** @var \App\Models\MissionService $service */
    @endphp

    <div class="mx-auto max-w-4xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Service audité</p>
                <h1 class="dgcpt-page-title">{{ $service->nom }}</h1>
                <p class="text-sm text-[#9FB3C8]">{{ $mission->organisation }}</p>
            </div>
            <a href="{{ route('services.index', $mission) }}" class="dgcpt-btn-outline text-sm">← Tableau services</a>
        </div>

        <form method="POST" action="{{ route('missions.services.update', [$mission, $service]) }}" class="dgcpt-surface space-y-4 p-6 shadow-sm">
            @csrf
            @method('PUT')
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="dgcpt-label" for="code">Code</label>
                    <input id="code" name="code" type="text" value="{{ old('code', $service->code) }}" class="dgcpt-input font-mono text-sm" />
                </div>
                <div>
                    <label class="dgcpt-label" for="service_type">Type</label>
                    <input id="service_type" name="service_type" type="text" value="{{ old('service_type', $service->service_type) }}" class="dgcpt-input" />
                </div>
                <div class="sm:col-span-2">
                    <label class="dgcpt-label" for="nom">Nom</label>
                    <input id="nom" name="nom" type="text" required value="{{ old('nom', $service->nom) }}" class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label" for="responsable">Responsable (texte libre)</label>
                    <input id="responsable" name="responsable" type="text" value="{{ old('responsable', $service->responsable) }}" class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label" for="chef_service_user_id">Chef de service (compte DGCPT)</label>
                    <select id="chef_service_user_id" name="chef_service_user_id" class="dgcpt-input">
                        <option value="">— Non renseigné —</option>
                        @foreach ($eligibleChefUsers as $u)
                            <option value="{{ $u->id }}" @selected((string) old('chef_service_user_id', $service->chef_service_user_id) === (string) $u->id)>
                                {{ $u->displayName() }} — {{ $u->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="dgcpt-label" for="chef_service_nom">Chef de service (nom libre)</label>
                    <input id="chef_service_nom" name="chef_service_nom" type="text" value="{{ old('chef_service_nom', $service->chef_service_nom) }}" class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label" for="chef_service_fonction">Fonction</label>
                    <input id="chef_service_fonction" name="chef_service_fonction" type="text" value="{{ old('chef_service_fonction', $service->chef_service_fonction) }}" class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label" for="chef_service_email">Email</label>
                    <input id="chef_service_email" name="chef_service_email" type="email" value="{{ old('chef_service_email', $service->chef_service_email) }}" class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label" for="chef_service_telephone">Téléphone</label>
                    <input id="chef_service_telephone" name="chef_service_telephone" type="text" value="{{ old('chef_service_telephone', $service->chef_service_telephone) }}" class="dgcpt-input" />
                </div>
                <div class="sm:col-span-2">
                    <label class="dgcpt-label" for="service_scope">Périmètre fonctionnel</label>
                    <input id="service_scope" name="service_scope" type="text" value="{{ old('service_scope', $service->service_scope) }}" class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label" for="audit_priority">Priorité audit</label>
                    <input id="audit_priority" name="audit_priority" type="text" value="{{ old('audit_priority', $service->audit_priority) }}" class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label" for="risk_level">Niveau risque</label>
                    <input id="risk_level" name="risk_level" type="text" value="{{ old('risk_level', $service->risk_level) }}" class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label" for="audit_status">Statut audit</label>
                    <select id="audit_status" name="audit_status" class="dgcpt-input">
                        @foreach (\App\Models\Service::auditStatusLabels() as $val => $lab)
                            <option value="{{ $val }}" @selected(old('audit_status', $service->audit_status ?? \App\Models\Service::AUDIT_STATUS_PENDING) === $val)>{{ $lab }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="dgcpt-label" for="description">Description</label>
                    <textarea id="description" name="description" rows="2" class="dgcpt-textarea w-full">{{ old('description', $service->description) }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="dgcpt-label" for="observations">Observations terrain</label>
                    <textarea id="observations" name="observations" rows="3" class="dgcpt-textarea w-full">{{ old('observations', $service->observations) }}</textarea>
                </div>
                <div class="flex items-center gap-2 sm:col-span-2">
                    <input id="active" type="checkbox" name="active" value="1" class="rounded border-[rgba(0,209,255,0.35)]" @checked(old('active', $service->active)) />
                    <label for="active" class="text-sm text-[#E6EEF8]">Service actif</label>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="dgcpt-btn-primary">Enregistrer</button>
            </div>
        </form>

        @can('delete', $service)
            <form method="POST" action="{{ route('missions.services.destroy', [$mission, $service]) }}" class="dgcpt-surface border-[#FF5A5A]/25 p-4" onsubmit="return confirm('Archiver ce service ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm font-semibold text-[#FF5A5A] hover:underline">Archiver le service</button>
            </form>
        @endcan
    </div>
</x-app-layout>
