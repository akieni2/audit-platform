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

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="dgcpt-surface p-4 shadow-sm">
                <p class="dgcpt-card-title">Actifs</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-[#E6EEF8]">{{ $stats['active'] }}</p>
            </div>
            <div class="dgcpt-surface p-4 shadow-sm">
                <p class="dgcpt-card-title">Désactivés</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-[#E6EEF8]">{{ $stats['inactive'] }}</p>
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
            <label class="inline-flex items-center gap-2 text-sm text-[#9FB3C8]">
                <input type="checkbox" name="inactive_only" value="1" @checked(!empty($filters['inactive_only'])) class="rounded border-[rgba(0,209,255,0.35)] bg-[#050816] text-[#00D1FF] focus:ring-[#00D1FF]" />
                Désactivés seulement
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
                                @if ($u->active)
                                    <span class="inline-flex rounded-lg border border-[rgba(0,168,107,0.35)] bg-[#0B1220] px-2 py-0.5 text-xs font-semibold text-[#00A86B]">Actif</span>
                                @else
                                    <span class="inline-flex rounded-lg border border-[rgba(148,163,184,0.25)] bg-[#10192B] px-2 py-0.5 text-xs font-semibold text-[#9FB3C8]">Désactivé</span>
                                @endif
                            </td>
                            <td class="space-x-2 whitespace-nowrap text-right">
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
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 text-[#9FB3C8]">
            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
