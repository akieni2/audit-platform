<x-app-layout>
    @php
        /** @var \App\Models\Mission $mission */
        /** @var \Illuminate\Support\Collection<int, \App\Models\MissionService> $services */
    @endphp

    <div class="mx-auto max-w-6xl space-y-8 px-0 py-2">

        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Mission</p>

                <h1 class="dgcpt-page-title">
                    Services audités
                </h1>

                <p class="text-sm text-[#9FB3C8]">
                    {{ $mission->organisation }}

                    @if ($mission->reference)
                        <span class="font-mono text-[#00D1FF]">
                            • {{ $mission->reference }}
                        </span>
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('missions.show', $mission) }}"
                   class="dgcpt-btn-outline text-sm">
                    ? Fiche mission
                </a>

                <a href="{{ route('module.risques') }}"
                   class="dgcpt-btn-outline text-sm">
                    Risques
                </a>
            </div>
        </div>

        @can('manageServices', $mission)
            <div class="dgcpt-surface p-6 shadow-sm">

                <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">
                    Ajouter un service
                </h2>

                <form method="POST"
                      action="{{ route('missions.services.store', $mission) }}"
                      class="mt-4 grid gap-4 sm:grid-cols-2">

                    @csrf

                    <div>
                        <label class="dgcpt-label" for="svc-code">
                            Code (optionnel)
                        </label>

                        <input id="svc-code"
                               name="code"
                               type="text"
                               class="dgcpt-input font-mono text-sm" />
                    </div>

                    <div>
                        <label class="dgcpt-label" for="svc-type">
                            Type de service
                        </label>

                        <input id="svc-type"
                               name="service_type"
                               type="text"
                               class="dgcpt-input"
                               placeholder="Ex: RH, SI, Recettes" />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="dgcpt-label" for="svc-nom">
                            Nom du service
                        </label>

                        <input id="svc-nom"
                               name="nom"
                               type="text"
                               required
                               class="dgcpt-input" />
                    </div>

                    <div>
                        <label class="dgcpt-label" for="svc-resp">
                            Responsable
                        </label>

                        <input id="svc-resp"
                               name="responsable"
                               type="text"
                               class="dgcpt-input" />
                    </div>

                    <div>
                        <label class="dgcpt-label" for="svc-risk">
                            Niveau de risque
                        </label>

                        <input id="svc-risk"
                               name="risk_level"
                               type="text"
                               class="dgcpt-input"
                               placeholder="Ex: Moyen" />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="dgcpt-label" for="svc-desc">
                            Description
                        </label>

                        <textarea id="svc-desc"
                                  name="description"
                                  rows="2"
                                  class="dgcpt-textarea w-full"></textarea>
                    </div>

                    <div class="flex items-center gap-2 sm:col-span-2">
                        <input id="svc-active"
                               type="checkbox"
                               name="active"
                               value="1"
                               checked
                               class="rounded border-[rgba(0,209,255,0.35)]" />

                        <label for="svc-active"
                               class="text-sm text-[#E6EEF8]">
                            Service actif dans le périmètre d’audit
                        </label>
                    </div>

                    <div class="sm:col-span-2">
                        <button type="submit"
                                class="dgcpt-btn-primary">
                            Créer le service
                        </button>
                    </div>

                </form>
            </div>
        @endcan

        <div class="dgcpt-surface overflow-hidden p-0 shadow-sm">

            <div class="border-b border-[rgba(0,209,255,0.12)] px-6 py-4">
                <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">
                    Tableau des services
                </h2>
            </div>

            <div class="overflow-x-auto">

                <table class="dgcpt-table min-w-full text-sm">

                    <thead>
                        <tr>
                            <th class="text-left">Service</th>
                            <th class="text-left">Responsable</th>
                            <th class="text-left">Risque</th>
                            <th class="text-center">Entretiens</th>
                            <th class="text-center">Risques</th>
                            <th class="text-center">Documents</th>
                            <th class="text-left">Progression</th>
                            <th class="text-left">Statut</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($services as $s)

                            @php
                                $progressList = $s->entretiens
                                    ->map(fn (\App\Models\Entretien $e) => $e->questionnaireCompletionPercent())
                                    ->filter();

                                $avgProgress = $progressList->isNotEmpty()
                                    ? (int) round($progressList->avg())
                                    : null;
                            @endphp

                            <tr>

                                <td>
                                    <span class="font-semibold text-[#E6EEF8]">
                                        {{ $s->nom }}
                                    </span>

                                    @if ($s->code)
                                        <span class="ml-1 font-mono text-xs text-[#00D1FF]">
                                            {{ $s->code }}
                                        </span>
                                    @endif

                                    @if ($s->service_type)
                                        <p class="text-xs text-[#9FB3C8]">
                                            {{ $s->service_type }}
                                        </p>
                                    @endif
                                </td>

                                <td class="text-[#9FB3C8]">
                                    {{ $s->responsableDisplay() }}
                                </td>

                                <td class="text-[#9FB3C8]">
                                    {{ $s->risk_level ?: '—' }}
                                </td>

                                <td class="text-center font-mono text-[#E6EEF8]">
                                    {{ $s->entretiens_count }}
                                </td>

                                <td class="text-center font-mono text-[#E6EEF8]">
                                    {{ $s->identified_risks_count }}
                                </td>

                                <td class="text-center font-mono text-[#E6EEF8]">
                                    {{ $s->mission_documents_count }}
                                </td>

                                <td class="text-[#9FB3C8]">
                                    {{ $avgProgress !== null ? $avgProgress.'%' : '—' }}
                                </td>

                                <td>
                                    <span class="rounded border border-[rgba(0,209,255,0.25)] px-2 py-0.5 text-xs">
                                        {{
                                            \App\Models\Service::auditStatusLabels()[
                                                $s->audit_status ?? \App\Models\Service::AUDIT_STATUS_PENDING
                                            ]
                                            ??
                                            ($s->audit_status ?? \App\Models\Service::AUDIT_STATUS_PENDING)
                                        }}
                                    </span>
                                </td>

                                <td class="text-right">
                                    <div class="flex flex-col items-end gap-1">

                                        <a href="{{ route('missions.services.edit', [$mission, $s]) }}"
                                           class="text-xs font-semibold text-[#00D1FF] hover:underline">
                                            Modifier
                                        </a>

                                        <a href="{{ route('entretiens.index', $s->id) }}"
                                           class="text-xs font-semibold text-[#00A86B] hover:underline">
                                            Conduire entretien
                                        </a>

                                        <a href="{{ route('module.risques') }}"
                                           class="text-xs text-[#9FB3C8] hover:underline">
                                            Risques
                                        </a>

                                        <a href="{{ route('missions.services.documents.index', [$mission, $s]) }}"
                                           class="text-xs text-[#9FB3C8] hover:underline">
                                            Documents
                                        </a>

                                        <span class="text-xs text-[#6B7F95]">
                                            Rapport service bientôt disponible
                                        </span>

                                    </div>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="9"
                                    class="py-8 text-center text-[#9FB3C8]">
                                    Aucun service structuré pour cette mission.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>
        </div>

        <div class="dgcpt-surface border-[rgba(0,209,255,0.08)] p-4 text-sm text-[#9FB3C8] ring-1 ring-[rgba(0,209,255,0.06)]">

            <p class="font-semibold text-[#E6EEF8]">
                SWOT & RACI
            </p>

            <p class="mt-1">
                Préparation technique en base
                (
                <span class="font-mono text-[#00D1FF]">
                    mission_swot_previews
                </span>,
                <span class="font-mono text-[#00D1FF]">
                    mission_raci_previews
                </span>
                )
                — interface dédiée prévue Phase 2C.
            </p>

        </div>

    </div>
</x-app-layout>