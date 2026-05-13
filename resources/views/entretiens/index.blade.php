<x-app-layout>
    @php
        /** @var \App\Models\Service $service */
        /** @var \Illuminate\Support\Collection<int, \App\Models\Entretien> $entretiens */
        /** @var \Illuminate\Support\Collection $templateChoices */
        /** @var \App\Models\Mission|null $mission */
    @endphp

    <div class="mx-auto max-w-5xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Entretiens d’audit</p>
                <h1 class="dgcpt-page-title">{{ $service->nom }}</h1>
                @if ($mission)
                    <p class="text-sm text-[#9FB3C8]">
                        Mission
                        <a href="{{ route('missions.show', $mission) }}" class="font-mono font-semibold text-[#00D1FF] hover:underline">{{ $mission->reference ?: 'n°'.$mission->id }}</a>
                        @if ($mission->organisation)
                            <span class="text-[#6B7F95]">·</span> {{ \Illuminate\Support\Str::limit($mission->organisation, 48) }}
                        @endif
                    </p>
                @endif
            </div>
            <a href="{{ route('questionnaire-builder.index') }}" class="dgcpt-btn-outline text-sm">Questionnaire Builder</a>
        </div>

        <div class="dgcpt-surface p-6 shadow-sm">
            <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Nouvel entretien</h2>
            <form method="POST" action="{{ route('entretiens.store') }}" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="service_id" value="{{ $service->id }}">
                <input type="hidden" name="mission_id" value="{{ $service->mission_id }}">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="dgcpt-label" for="responsable_nom">Nom du responsable</label>
                        <input id="responsable_nom" name="responsable_nom" type="text" class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label" for="role">Rôle / fonction</label>
                        <input id="role" name="role" type="text" class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label" for="chef_hierarchique">Chef hiérarchique</label>
                        <input id="chef_hierarchique" name="chef_hierarchique" type="text" class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label" for="auditeur">Auditeur</label>
                        <input id="auditeur" name="auditeur" type="text" class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label" for="date_entretien">Date entretien</label>
                        <input id="date_entretien" name="date_entretien" type="date" class="dgcpt-input" />
                    </div>
                    @if ($mission && $templateChoices->isNotEmpty())
                        <div class="sm:col-span-2">
                            <label class="dgcpt-label" for="questionnaire_template_id">Modèle de questionnaire (optionnel)</label>
                            <select id="questionnaire_template_id" name="questionnaire_template_id" class="dgcpt-input">
                                <option value="">— Sans modèle dynamique —</option>
                                @foreach ($templateChoices as $tpl)
                                    <option value="{{ $tpl->id }}">{{ $tpl->name }} @if($tpl->mission_type) ({{ $tpl->mission_type }}) @endif</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-[#9FB3C8]">Réservé à la gouvernance de mission. L’équipe pourra ensuite conduire le questionnaire section par section.</p>
                        </div>
                    @endif
                </div>
                <div>
                    <label class="dgcpt-label" for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="2" class="dgcpt-textarea w-full"></textarea>
                </div>
                <button type="submit" class="dgcpt-btn-primary">Enregistrer l’entretien</button>
            </form>
        </div>

        <div class="dgcpt-surface overflow-hidden p-0 shadow-sm">
            <div class="border-b border-[rgba(0,209,255,0.12)] px-6 py-4">
                <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Entretiens enregistrés</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="dgcpt-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Responsable</th>
                            <th class="text-left">Rôle</th>
                            <th class="text-left">Date</th>
                            <th class="text-left">Statut</th>
                            <th class="text-left">Questionnaire</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entretiens as $e)
                            <tr>
                                <td class="font-semibold text-[#E6EEF8]">{{ $e->responsable_nom ?: '—' }}</td>
                                <td class="text-[#9FB3C8]">{{ $e->role ?: '—' }}</td>
                                <td class="font-mono text-[#9FB3C8]">{{ $e->date_entretien ?: '—' }}</td>
                                <td class="text-xs text-[#9FB3C8]">{{ \App\Models\Entretien::statusLabels()[$e->status] ?? ($e->status ?: '—') }}</td>
                                <td>
                                    @if ($e->questionnaireTemplate)
                                        <span class="text-[#E6EEF8]">{{ $e->questionnaireTemplate->name }}</span>
                                    @else
                                        <span class="text-[#6B7F95]">—</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        @can('conductQuestionnaire', $e)
                                            @if ($e->questionnaire_template_id)
                                                <a href="{{ route('entretiens.conduite.show', $e) }}" class="text-xs font-semibold text-[#00D1FF] hover:underline">Conduite</a>
                                            @endif
                                        @endcan
                                        @can('completeEntretien', $e)
                                            @if (! in_array($e->status, [\App\Models\Entretien::STATUS_COMPLETED, \App\Models\Entretien::STATUS_VALIDATED], true))
                                                <form method="POST" action="{{ route('entretiens.complete', $e) }}" class="inline" onsubmit="return confirm('Marquer cet entretien comme complété ?');">
                                                    @csrf
                                                    <button type="submit" class="text-xs font-semibold text-[#00A86B] hover:underline">Compléter</button>
                                                </form>
                                            @endif
                                        @endcan
                                        @can('attachTemplate', $e)
                                            @if (! $e->questionnaire_template_id && $templateChoices->isNotEmpty())
                                                <form method="POST" action="{{ route('entretiens.questionnaire.attach', $e) }}" class="inline-flex items-center gap-1">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="questionnaire_template_id" class="rounded border border-[rgba(0,209,255,0.22)] bg-[#050816] px-2 py-1 text-xs text-[#E6EEF8]">
                                                        @foreach ($templateChoices as $tpl)
                                                            <option value="{{ $tpl->id }}">{{ \Illuminate\Support\Str::limit($tpl->name, 32) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="text-xs font-semibold text-[#00A86B] hover:underline">Affecter</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-[#9FB3C8]">Aucun entretien pour ce service.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
