<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="dgcpt-surface p-8 shadow-sm">
            <p class="dgcpt-card-title">Exécution du workflow</p>
            <h1 class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ $stage->name }}</h1>
            <p class="mt-3 text-sm text-[#9FB3C8]">Exécution RACI intégrée à la représentation visuelle du workflow.</p>

            <div class="mt-6 grid gap-4 md:grid-cols-4">
                @foreach (($raciView['kpis'] ?? []) as $label => $value)
                    <div class="rounded-2xl bg-[rgba(255,255,255,0.03)] p-4">
                        <p class="text-xs uppercase tracking-wide text-[#73D8FF]">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $label)) }}</p>
                        <p class="mt-2 text-2xl font-bold text-[#E6EEF8]">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('workflow-runtime.stage.submit', ['mission' => $instance->mission_id, 'stage' => $stage]) }}" class="mt-6 grid gap-4 md:grid-cols-2">
                @csrf
                @if ($stage->resolvedStageType()?->value === 'raci_assignment')
                    <div>
                        <label class="dgcpt-label">Template RACI</label>
                        <input type="hidden" name="raci_template_id" value="{{ $selectedTemplate?->id }}" />
                        <input type="text" value="{{ $selectedTemplate?->name ?? 'Template non configure' }}" disabled class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Processus</label>
                        <input name="process_label" type="text" required class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Role RACI (ID)</label>
                        <input name="raci_role_id" type="number" required class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Utilisateur assigne (ID)</label>
                        <input name="assigned_user_id" type="number" class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Type</label>
                        <select name="role_type" class="dgcpt-input">
                            <option value="responsible">Responsible</option>
                            <option value="accountable">Responsable final</option>
                            <option value="consulted">Consulted</option>
                            <option value="informed">Informed</option>
                        </select>
                    </div>
                    <div>
                        <label class="dgcpt-label">Niveau</label>
                        <select name="responsibility_level" class="dgcpt-input">
                            <option value="low">Faible</option>
                            <option value="moderate">Modere</option>
                            <option value="high">Eleve</option>
                            <option value="critical">Critique</option>
                        </select>
                    </div>
                @else
                    <div>
                        <label class="dgcpt-label">Statut</label>
                        <select name="status" class="dgcpt-input">
                            <option value="approved">Approuver</option>
                            <option value="rejected">Rejeter</option>
                            <option value="pending">Reporter</option>
                        </select>
                    </div>
                    <div>
                        <label class="dgcpt-label">Notes</label>
                        <input name="notes" type="text" class="dgcpt-input" />
                    </div>
                @endif
                <div class="md:col-span-2">
                    <button type="submit" class="dgcpt-btn-primary">
                        {{ $stage->resolvedStageType()?->value === 'raci_assignment' ? 'Enregistrer affectation' : 'Valider la matrice' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
