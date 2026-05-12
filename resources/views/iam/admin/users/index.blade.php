<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2 sm:px-0">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="dgcpt-card-title">Administration IAM</p>
                <h1 class="dgcpt-page-title">Utilisateurs</h1>
                <p class="mt-1 text-sm dgcpt-text-muted">
                    Annuaire, affectations département / catégorie, désactivation et réinitialisation sécurisée.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.home') }}" class="dgcpt-link text-sm">Tableau de bord admin</a>
                <a href="{{ route('admin.users.create') }}"
                   class="inline-flex justify-center rounded-xl bg-gradient-to-r from-[#0A2A66] to-blue-950 px-4 py-2 text-sm font-bold uppercase tracking-wider text-white ring-1 ring-[rgba(0,209,255,0.25)]">
                    Nouvel utilisateur
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 bg-[#0B1220] px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="dgcpt-surface border-[#FF5A5A]/40 px-4 py-3 text-sm text-[#FF5A5A] ring-1 ring-[rgba(255,90,90,0.2)]">
                <ul class="ms-5 list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="dgcpt-surface p-4 shadow-sm">
                <p class="dgcpt-card-title">Actifs</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-[#E6EEF8]">{{ $stats['active'] }}</p>
            </div>
            <div class="dgcpt-surface p-4 shadow-sm">
                <p class="dgcpt-card-title">Désactivés</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-[#E6EEF8]">{{ $stats['inactive'] }}</p>
            </div>
            <div class="dgcpt-surface border-[#FF5A5A]/25 p-4 shadow-sm ring-1 ring-[rgba(255,90,90,0.12)]">
                <p class="dgcpt-card-title text-[#FF5A5A]/90">Supprimés IAM</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-[#FF5A5A]">{{ $stats['deleted'] }}</p>
            </div>
            <div class="dgcpt-surface p-4 shadow-sm sm:col-span-2">
                <p class="dgcpt-card-title mb-2">Connexions récentes</p>
                <ul class="space-y-1 text-sm text-[#E6EEF8]">
                    @forelse ($recentLogins as $u)
                        <li class="flex justify-between gap-2">
                            <span>{{ $u->displayName() }}</span>
                            <span class="whitespace-nowrap text-[#9FB3C8]">{{ optional($u->last_login_at)->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="text-[#9FB3C8]">Aucune connexion enregistrée.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="dgcpt-surface p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-[#E6EEF8]">Par département (actifs)</h2>
                <ul class="space-y-2 text-sm text-[#E6EEF8]">
                    @foreach ($byDepartment as $d)
                        <li class="flex justify-between gap-2">
                            <span><span class="inline-flex items-center rounded-lg border border-[rgba(0,209,255,0.2)] bg-[#10192B] px-2 py-0.5 text-xs font-semibold text-[#00D1FF]">{{ $d->code }}</span> <span class="text-[#9FB3C8]">{{ $d->name }}</span></span>
                            <span class="font-semibold text-[#E6EEF8]">{{ $d->users_count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="dgcpt-surface p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-[#E6EEF8]">Par rôle institutionnel (actifs)</h2>
                <ul class="space-y-2 text-sm text-[#E6EEF8]">
                    @foreach ($byRole as $r)
                        <li class="flex justify-between">
                            <span class="text-[#9FB3C8]">{{ $r->name }}</span>
                            <span class="font-semibold">{{ $r->users_count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <form method="get" action="{{ route('admin.users.index') }}" class="dgcpt-filter-bar flex-wrap">
            <div class="min-w-[180px] flex-1">
                <label class="dgcpt-card-title mb-1 block">Recherche</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, email, matricule…"
                       class="block w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8] placeholder:text-[#9FB3C8]/70 focus:border-[#00D1FF] focus:outline-none focus:ring-1 focus:ring-[#00D1FF]" />
            </div>
            <div>
                <label class="dgcpt-card-title mb-1 block">Département</label>
                <select name="department_id" class="block rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8] focus:border-[#00D1FF] focus:outline-none focus:ring-1 focus:ring-[#00D1FF]">
                    <option value="">— Tous —</option>
                    @foreach ($departments as $d)
                        <option value="{{ $d->id }}" @selected(($filters['department_id'] ?? '') == $d->id)>{{ $d->code }} — {{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="dgcpt-card-title mb-1 block">Vue annuaire</label>
                <select name="account_view" class="block rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8] focus:border-[#00D1FF] focus:outline-none focus:ring-1 focus:ring-[#00D1FF]">
                    <option value="" @selected(($filters['account_view'] ?? '') === '')>Tous (non supprimés)</option>
                    <option value="active" @selected(($filters['account_view'] ?? '') === 'active')>Actifs seulement</option>
                    <option value="inactive" @selected(($filters['account_view'] ?? '') === 'inactive')>Désactivés seulement</option>
                    <option value="deleted" @selected(($filters['account_view'] ?? '') === 'deleted')>Supprimés (IAM)</option>
                </select>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-[#9FB3C8]">
                <input type="checkbox" name="inactive_only" value="1" @checked(!empty($filters['inactive_only'])) class="rounded border-[rgba(0,209,255,0.35)] bg-[#050816] text-[#00D1FF] focus:ring-[#00D1FF]" />
                Désactivés (ancien filtre)
            </label>
            <button type="submit" class="rounded-xl bg-[#10192B] px-4 py-2 text-sm font-bold uppercase tracking-wider text-[#E6EEF8] ring-1 ring-[rgba(0,209,255,0.25)] hover:bg-[#122038]">Filtrer</button>
            <a href="{{ route('admin.users.index') }}" class="dgcpt-link py-2 text-sm">Réinitialiser</a>
        </form>

        <div class="dgcpt-table-wrap shadow-sm">
            <table class="dgcpt-table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Département</th>
                        <th>Catégorie</th>
                        <th>Statut</th>
                        @if(($filters['account_view'] ?? '') === 'deleted')
                            <th>Supprimé le</th>
                            <th>Supprimé par</th>
                        @endif
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td>
                                <div class="font-semibold text-[#E6EEF8]">{{ $u->displayName() }}</div>
                                <div class="text-xs text-[#9FB3C8]">{{ $u->email }}</div>
                            </td>
                            <td>
                                @if ($u->department)
                                    <span class="inline-flex rounded-lg border border-[rgba(0,209,255,0.25)] bg-[#10192B] px-2 py-0.5 text-xs font-semibold text-[#00D1FF]">{{ $u->department->code }}</span>
                                @else
                                    <span class="text-[#9FB3C8]">—</span>
                                @endif
                            </td>
                            <td class="text-[#9FB3C8]">
                                @if ($u->institutionalRole)
                                    {{ $u->institutionalRole->name }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($u->trashed())
                                    <span class="inline-flex rounded-lg border border-[rgba(255,90,90,0.35)] bg-[#10192B] px-2 py-0.5 text-xs font-semibold text-[#FF5A5A]">Supprimé IAM</span>
                                @elseif ($u->active)
                                    <span class="inline-flex rounded-lg border border-[rgba(0,168,107,0.35)] bg-[#0B1220] px-2 py-0.5 text-xs font-semibold text-[#00A86B]">Actif</span>
                                @else
                                    <span class="inline-flex rounded-lg border border-[rgba(148,163,184,0.25)] bg-[#10192B] px-2 py-0.5 text-xs font-semibold text-[#9FB3C8]">Désactivé</span>
                                @endif
                            </td>
                            @if(($filters['account_view'] ?? '') === 'deleted')
                                <td class="whitespace-nowrap text-xs text-[#9FB3C8]">{{ $u->deleted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="text-xs text-[#9FB3C8]">{{ $u->deletedBy?->displayName() ?? '—' }}</td>
                            @endif
                            <td class="space-x-2 whitespace-nowrap text-right">
                                @unless($u->trashed())
                                    <a href="{{ route('admin.users.edit', $u) }}" class="text-xs font-semibold text-[#00D1FF] hover:underline">Modifier</a>
                                    @if ($u->active && auth()->id() !== $u->id)
                                        <form method="post" action="{{ route('admin.users.deactivate', $u) }}" class="inline" onsubmit="return confirm('Désactiver cet utilisateur ?');">
                                            @csrf
                                            <button type="submit" class="text-xs font-semibold text-[#FF5A5A] hover:underline">Désactiver</button>
                                        </form>
                                    @endif
                                    <form method="post" action="{{ route('admin.users.password-reset', $u) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-[#9FB3C8] hover:text-[#E6EEF8] hover:underline">Reset MDP</button>
                                    </form>
                                    @can('deleteFromAdministration', $u)
                                        @if (auth()->id() !== $u->id)
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-1 text-xs font-semibold text-[#FF5A5A] hover:underline"
                                                title="Supprimer le compte (soft delete)"
                                                x-data
                                                @click="$dispatch('open-modal', 'confirm-delete-user-{{ $u->id }}')"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m2 0v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6h12zM10 11v6M14 11v6"/></svg>
                                                Supprimer
                                            </button>
                                        @endif
                                    @endcan
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @foreach ($users as $u)
            @can('deleteFromAdministration', $u)
                @if (auth()->id() !== $u->id && ! $u->trashed())
                    <x-modal name="confirm-delete-user-{{ $u->id }}" maxWidth="md" focusable>
                        <form method="post" action="{{ route('admin.users.destroy', $u) }}" class="p-6">
                            @csrf
                            @method('DELETE')
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#FF5A5A]/15 text-[#FF5A5A]" aria-hidden="true">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2m2 0v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6h12zM10 11v6M14 11v6"/></svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-lg font-bold text-[#E6EEF8]">Supprimer définitivement l’accès</h3>
                                    <p class="mt-2 text-sm leading-relaxed text-[#9FB3C8]">
                                        Cette action supprimera définitivement l’accès utilisateur pour <strong class="text-[#E6EEF8]">{{ $u->displayName() }}</strong>.
                                        Les traces institutionnelles et historiques seront conservées.
                                    </p>
                                </div>
                            </div>
                            <div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-white/10 pt-4">
                                <button type="button" class="rounded-lg border border-[rgba(0,209,255,0.25)] bg-[#10192B] px-4 py-2 text-sm font-semibold text-[#E6EEF8] hover:bg-[#122038]" @click="$dispatch('close-modal', 'confirm-delete-user-{{ $u->id }}')">
                                    Annuler
                                </button>
                                <button type="submit" class="rounded-lg border border-[rgba(255,90,90,0.45)] bg-[#7f1d1d] px-4 py-2 text-sm font-bold text-white hover:bg-[#991b1b]">
                                    Supprimer définitivement
                                </button>
                            </div>
                        </form>
                    </x-modal>
                @endif
            @endcan
        @endforeach

        <div class="mt-4 text-[#9FB3C8]">
            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
