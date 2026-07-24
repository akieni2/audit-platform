<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-6 px-0 py-2">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Opérations</p>
                <h1 class="dgcpt-page-title">Missions</h1>
                <p class="mt-1 text-sm dgcpt-text-muted">Workflow ascendant — rattachement départemental.</p>
            </div>
            @if (auth()->user()?->can('create', \App\Models\Mission::class))
                <a href="{{ route('missions.create') }}" class="inline-flex rounded-xl bg-gradient-to-r from-[#0A2A66] to-blue-950 px-4 py-2 text-sm font-bold uppercase tracking-wider text-white shadow-lg shadow-cyan-500/15 ring-1 ring-[rgba(0,209,255,0.25)] hover:shadow-cyan-500/25">
                    Nouvelle mission
                </a>
            @endif
        </div>

        <form method="get" action="{{ route('missions.index') }}" class="dgcpt-filter-bar">
            @if(request()->filled('department'))
                <input type="hidden" name="department" value="{{ request('department') }}" />
            @endif
            <div class="min-w-[10rem] flex-1">
                <label for="filter-q" class="dgcpt-card-title">Recherche</label>
                <input id="filter-q" name="q" type="search" value="{{ request('q') }}"
                       placeholder="Référence, objet, organisation…"
                       class="mt-1 block w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8] placeholder:text-[#9FB3C8]/70 focus:border-[#00D1FF] focus:outline-none focus:ring-1 focus:ring-[#00D1FF]" />
            </div>
            <div class="w-full sm:w-52">
                <label for="filter-status" class="dgcpt-card-title">Statut workflow</label>
                <select id="filter-status" name="status"
                        class="mt-1 block w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8] focus:border-[#00D1FF] focus:outline-none focus:ring-1 focus:ring-[#00D1FF]">
                    <option value="">Tous</option>
                    <option value="{{ \App\Models\Mission::STATUS_BROUILLON }}" @selected(request('status') === \App\Models\Mission::STATUS_BROUILLON)>Brouillon</option>
                    <option value="{{ \App\Models\Mission::STATUS_EN_COURS }}" @selected(request('status') === \App\Models\Mission::STATUS_EN_COURS)>En cours</option>
                    <option value="{{ \App\Models\Mission::STATUS_CLOTUREE }}" @selected(request('status') === \App\Models\Mission::STATUS_CLOTUREE)>Clôturée</option>
                    <option value="{{ \App\Models\Mission::STATUS_VALIDEE_IS }}" @selected(request('status') === \App\Models\Mission::STATUS_VALIDEE_IS)>Validée IS</option>
                    <option value="{{ \App\Models\Mission::STATUS_VALIDEE_COPRI }}" @selected(request('status') === \App\Models\Mission::STATUS_VALIDEE_COPRI)>Validée COPRI</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex rounded-xl bg-gradient-to-r from-[#0A2A66] to-blue-950 px-4 py-2 text-sm font-bold uppercase tracking-wider text-white ring-1 ring-[rgba(0,209,255,0.25)]">
                    Filtrer
                </button>
                <a href="{{ route('missions.index', array_filter(request()->only(['department']))) }}" class="dgcpt-btn-outline">
                    Réinitialiser
                </a>
            </div>
        </form>

        <div class="dgcpt-table-wrap shadow-sm">
            <table class="dgcpt-table mission-responsive-table">
                <thead>
                    <tr>
                        <th>Organisation</th>
                        <th>Statut</th>
                        <th>Votre rôle</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($missions as $mission)
                        <tr>
                            <td class="font-semibold" data-label="Organisation">
                                <a href="{{ route('missions.show', $mission) }}" class="text-[#00D1FF] hover:underline">
                                    {{ $mission->organisation }}
                                </a>
                                @if ($mission->reference)
                                    <span class="mt-0.5 block font-mono text-xs font-normal text-[#9FB3C8]">{{ $mission->reference }}</span>
                                @endif
                            </td>
                            <td data-label="Statut">
                                <x-mission-status-badge :status="$mission->mission_status" />
                            </td>
                            <td data-label="Votre rôle">
                                @php($myMembership = $mission->missionTeamMembers->first())
                                @if ($myMembership)
                                    <span class="font-semibold {{ $myMembership->mission_role === \App\Models\MissionTeamMember::ROLE_CHEF_MISSION ? 'text-[#7EF2BE]' : 'text-[#BFD2E6]' }}">
                                        {{ \App\Models\MissionTeamMember::missionRoleLabels()[$myMembership->mission_role] ?? $myMembership->mission_role }}
                                    </span>
                                @elseif (auth()->user()?->can('governMission', $mission))
                                    <span class="text-[#BFD2E6]">Responsable de l’unité</span>
                                @else
                                    <span class="text-[#9FB3C8]">Agent de l’unité</span>
                                @endif
                            </td>
                            <td class="text-[#9FB3C8]" data-label="Début">{{ $mission->date_debut }}</td>
                            <td class="text-[#9FB3C8]" data-label="Fin">{{ $mission->date_fin ?? '—' }}</td>
                            <td data-label="Actions">
                                <div class="flex flex-col gap-1 text-xs">
                                    <a href="{{ route('missions.show', $mission) }}" class="font-semibold text-[#00D1FF] hover:underline">Fiche</a>
                                    <a href="{{ route('services.index', $mission) }}" class="text-[#9FB3C8] hover:text-[#E6EEF8] hover:underline">Services</a>
                                    <a href="{{ route('missions.questionnaires.index', $mission) }}" class="font-semibold text-[#73D8FF] hover:underline">Questionnaires</a>
                                    <a href="{{ route('cartographie.index', $mission) }}" class="text-[#9FB3C8] hover:text-[#E6EEF8] hover:underline">Cartographie</a>
                                    <a href="{{ route('missions.rapport', $mission) }}" class="text-[#9FB3C8] hover:text-[#E6EEF8] hover:underline">PDF</a>
                                    @can('delete', $mission)
                                        <form method="POST" action="{{ route('missions.destroy', $mission) }}" onsubmit="return confirm('Supprimer cette mission en brouillon ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-left font-semibold text-[#FF5A5A] hover:underline">Supprimer</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-[#9FB3C8]">Aucune mission visible pour votre périmètre.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($missions->hasPages())
            <div class="mt-6 text-[#9FB3C8]">
                {{ $missions->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
