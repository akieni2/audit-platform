<x-app-layout>
    @php
        $labels = [
            'demarrer' => 'Passer en cours',
            'cloturer' => 'Clôturer la mission',
            'valider_is' => 'Valider (Inspection des Services)',
            'demander_corrections' => 'Demander des corrections',
            'valider_copri' => 'Valider stratégiquement (COPRI)',
            'renvoyer_copri' => 'Renvoyer pour révision (COPRI)',
        ];
    @endphp

    <div class="mx-auto max-w-4xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="space-y-2">
            <p class="dgcpt-card-title">Fiche mission</p>
            <h1 class="dgcpt-page-title">{{ $mission->organisation }}</h1>
            <p class="flex flex-wrap items-center gap-2 text-sm dgcpt-text-muted">
                <span>État workflow :</span>
                <x-mission-status-badge :status="$mission->mission_status" />
            </p>
            @if ($mission->department)
                <p class="text-sm dgcpt-text-muted">
                    <span class="font-mono font-semibold text-[#00D1FF]">{{ $mission->department->code }}</span>
                    — {{ $mission->department->name }}
                </p>
            @endif
        </div>

        <div class="dgcpt-surface p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Ordre de mission</h2>
                @can('update', $mission)
                    <a href="{{ route('missions.edit', $mission) }}" class="text-sm font-semibold text-[#00D1FF] hover:underline">
                        Modifier ordre et fiche
                    </a>
                @endcan
            </div>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-[#9FB3C8]">Référence mission</dt>
                    <dd class="font-semibold text-[#E6EEF8]">{{ $mission->reference ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[#9FB3C8]">Période d’audit</dt>
                    <dd class="font-semibold text-[#E6EEF8]">{{ $mission->periode_audit ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-[#9FB3C8]">Objet</dt>
                    <dd class="whitespace-pre-wrap font-semibold text-[#E6EEF8]">{{ $mission->objet ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[#9FB3C8]">Référence ordre de mission</dt>
                    <dd class="font-semibold text-[#E6EEF8]">{{ $mission->ordre_mission_reference ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[#9FB3C8]">Date ordre de mission</dt>
                    <dd class="font-semibold text-[#E6EEF8]">{{ $mission->date_ordre_mission?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[#9FB3C8]">Date début (mission)</dt>
                    <dd class="font-semibold text-[#E6EEF8]">{{ $mission->date_debut instanceof \Carbon\Carbon ? $mission->date_debut->format('d/m/Y') : ($mission->date_debut ?? '—') }}</dd>
                </div>
                <div>
                    <dt class="text-[#9FB3C8]">Date fin (mission)</dt>
                    <dd class="font-semibold text-[#E6EEF8]">{{ $mission->date_fin instanceof \Carbon\Carbon ? $mission->date_fin->format('d/m/Y') : ($mission->date_fin ?? '—') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-[#9FB3C8]">Observations générales</dt>
                    <dd class="whitespace-pre-wrap text-[#E6EEF8]">{{ $mission->observations_generales ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="dgcpt-surface p-6 shadow-sm">
            <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Équipe de mission</h2>
            <p class="mt-1 text-sm text-[#9FB3C8]">
                Rôles missionnels distincts du profil IAM — chef de mission, agents et experts affectés.
            </p>

            @if ($mission->missionTeamMembers->isEmpty())
                <p class="mt-4 text-sm text-[#9FB3C8]">Aucun membre n’est encore affecté.</p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="dgcpt-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Membre</th>
                                <th class="text-left">Rôle mission</th>
                                <th class="text-left">Désignation</th>
                                <th class="text-left">Affecté le</th>
                                @can('update', $mission)
                                    <th class="text-right">Actions</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mission->missionTeamMembers as $member)
                                <tr>
                                    <td class="font-semibold text-[#E6EEF8]">
                                        {{ $member->user?->displayName() ?? '—' }}
                                        @if ($member->is_lead || $member->mission_role === \App\Models\MissionTeamMember::ROLE_CHEF_MISSION)
                                            <span class="ml-2 rounded bg-[#0A2A66]/80 px-2 py-0.5 text-xs font-bold uppercase tracking-wide text-[#00D1FF] ring-1 ring-[rgba(0,209,255,0.35)]">Chef</span>
                                        @endif
                                    </td>
                                    <td class="text-[#9FB3C8]">{{ $missionRoleLabels[$member->mission_role] ?? $member->mission_role }}</td>
                                    <td class="text-[#9FB3C8]">{{ $member->designation ?: '—' }}</td>
                                    <td class="text-[#9FB3C8]">{{ $member->assigned_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    @can('update', $mission)
                                        <td class="text-right">
                                            <form method="POST" action="{{ route('missions.team-members.destroy', [$mission, $member]) }}" class="inline" onsubmit="return confirm('Retirer ce membre de l’équipe ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-semibold text-[#FF5A5A] hover:underline">Retirer</button>
                                            </form>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @can('update', $mission)
                @if ($eligibleTeamUsers->isEmpty())
                    <p class="mt-4 text-sm text-[#9FB3C8]">Aucun utilisateur disponible à affecter dans le périmètre autorisé (ou l’équipe est complète).</p>
                @else
                    <form method="POST" action="{{ route('missions.team-members.store', $mission) }}" class="mt-6 space-y-4 border-t border-[rgba(0,209,255,0.12)] pt-6">
                        @csrf
                        <p class="dgcpt-card-title">Ajouter un membre</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="dgcpt-label" for="team-user-id">Utilisateur</label>
                                <select id="team-user-id" name="user_id" required class="dgcpt-input">
                                    <option value="">— Choisir —</option>
                                    @foreach ($eligibleTeamUsers as $u)
                                        <option value="{{ $u->id }}">{{ $u->displayName() }} — {{ $u->email }}</option>
                                    @endforeach
                                </select>
                                @error('user_id')
                                    <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="dgcpt-label" for="team-role">Rôle missionnel</label>
                                <select id="team-role" name="mission_role" required class="dgcpt-input">
                                    @foreach ($missionRoleLabels as $slug => $label)
                                        <option value="{{ $slug }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('mission_role')
                                    <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="dgcpt-label" for="team-designation">Désignation (optionnel)</label>
                                <input id="team-designation" type="text" name="designation" value="{{ old('designation') }}" placeholder="ex. Expert SIG" class="dgcpt-input" />
                                @error('designation')
                                    <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <button type="submit" class="dgcpt-btn-primary">Ajouter à l’équipe</button>
                    </form>
                @endif
            @endcan
        </div>

        <div class="dgcpt-surface p-6 shadow-sm">
            <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Description</h2>
            <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-[#9FB3C8]">{{ $mission->description ?: '—' }}</p>
            @can('update', $mission)
                <p class="mt-4">
                    <a href="{{ route('missions.edit', $mission) }}" class="text-sm font-semibold text-[#00D1FF] hover:underline">
                        Modifier les informations
                    </a>
                </p>
            @endcan
        </div>

        @if (count($allowedActions) > 0)
            <div class="dgcpt-surface border-[#00D1FF]/25 p-6 shadow-sm ring-1 ring-[rgba(0,209,255,0.2)]">
                <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Décision institutionnelle</h2>
                <p class="mt-1 text-sm text-[#9FB3C8]">
                    Chaque transition est journalisée avec votre compte et un commentaire lorsque requis.
                </p>
                <form method="post" action="{{ route('missions.workflow', $mission) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="dgcpt-card-title mb-1 block">Action</label>
                        <select name="action" required class="mt-1 block w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8] focus:border-[#00D1FF] focus:outline-none focus:ring-1 focus:ring-[#00D1FF]">
                            @foreach ($allowedActions as $action)
                                <option value="{{ $action }}">{{ $labels[$action] ?? $action }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="dgcpt-card-title mb-1 block">Commentaire</label>
                        <textarea name="comment" rows="3" class="mt-1 block w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8] placeholder:text-[#9FB3C8]/60 focus:border-[#00D1FF] focus:outline-none focus:ring-1 focus:ring-[#00D1FF]" placeholder="Obligatoire pour demande de corrections ou renvoi COPRI"></textarea>
                        @error('comment')
                            <p class="mt-1 text-sm text-[#FF5A5A]">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="rounded-xl bg-gradient-to-r from-[#0A2A66] to-blue-950 px-4 py-2 text-sm font-bold uppercase tracking-wider text-white ring-1 ring-[rgba(0,209,255,0.3)]">
                        Enregistrer la décision
                    </button>
                </form>
            </div>
        @endif

        <div class="dgcpt-surface p-6 shadow-sm">
            <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Journal des validations</h2>
            @if ($mission->workflowEvents->isEmpty())
                <p class="mt-2 text-sm text-[#9FB3C8]">Aucune transition enregistrée pour le moment.</p>
            @else
                <ul class="mt-4 divide-y divide-[rgba(0,209,255,0.12)]">
                    @foreach ($mission->workflowEvents as $event)
                        <li class="py-3 text-sm">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <span class="font-semibold text-[#E6EEF8]">
                                    {{ $labels[$event->action] ?? $event->action }}
                                </span>
                                <span class="text-xs text-[#9FB3C8]">{{ $event->created_at?->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="text-xs text-[#9FB3C8]">
                                {{ $event->from_status }} → {{ $event->to_status }}
                                @if ($event->user)
                                    — {{ $event->user->displayName() }}
                                @endif
                            </p>
                            @if ($event->comment)
                                <p class="mt-1 text-[#E6EEF8]">{{ $event->comment }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="flex flex-wrap gap-4 text-sm">
            <a href="{{ route('services.index', $mission) }}" class="font-semibold text-[#00D1FF] hover:underline">Services audités</a>
            <a href="{{ route('processus.index', $mission) }}" class="font-semibold text-[#00D1FF] hover:underline">Processus</a>
            <a href="{{ route('cartographie.index', $mission) }}" class="font-semibold text-[#00D1FF] hover:underline">Cartographie</a>
            <a href="{{ route('missions.rapport', $mission) }}" class="font-semibold text-[#00D1FF] hover:underline">Rapport PDF</a>
            <a href="{{ route('missions.index') }}" class="dgcpt-text-muted text-sm hover:underline">← Liste des missions</a>
        </div>
    </div>
</x-app-layout>
