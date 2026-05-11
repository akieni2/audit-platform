<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2 sm:px-0">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="dgcpt-card-title">Console</p>
                <h1 class="dgcpt-page-title">Tableau de bord admin</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">Pilotage des comptes, sécurité et indicateurs d’activité.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('admin.users.create') }}" class="rounded-xl bg-[#10192B] px-4 py-2 font-bold uppercase tracking-wider text-[#E6EEF8] ring-1 ring-[rgba(0,209,255,0.25)] hover:bg-[#122038]">Créer utilisateur</a>
                <a href="{{ route('admin.users.index') }}" class="rounded-xl bg-gradient-to-r from-[#0A2A66] to-blue-950 px-4 py-2 font-bold uppercase tracking-wider text-white ring-1 ring-[rgba(0,209,255,0.3)]">Utilisateurs</a>
                @can('manageDepartments')
                    <a href="{{ route('admin.departments.index') }}" class="rounded-xl bg-[#10192B] px-4 py-2 font-bold uppercase tracking-wider text-[#E6EEF8] ring-1 ring-[rgba(0,209,255,0.25)] hover:bg-[#122038]">Pôles / départements</a>
                @endcan
                <a href="{{ route('admin.security.audit-logs') }}" class="dgcpt-btn-outline px-4 py-2 text-sm font-semibold">Journal sécurité</a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="dgcpt-surface p-4 shadow-sm">
                <p class="dgcpt-card-title">Actifs</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-[#E6EEF8]">{{ $stats['active'] }}</p>
            </div>
            <div class="dgcpt-surface p-4 shadow-sm">
                <p class="dgcpt-card-title">Inactifs</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-[#E6EEF8]">{{ $stats['inactive'] }}</p>
            </div>
            <div class="dgcpt-surface border-[#F4D000]/35 p-4 shadow-sm ring-1 ring-[rgba(244,208,0,0.2)]">
                <p class="dgcpt-card-title text-[#F4D000]">Mot de passe à changer</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-[#F4D000]">{{ $stats['must_change'] }}</p>
            </div>
            <div class="dgcpt-surface border-[#FF5A5A]/35 p-4 shadow-sm ring-1 ring-[rgba(255,90,90,0.2)]">
                <p class="dgcpt-card-title text-[#FF5A5A]">Verrouillés (maintenant)</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-[#FF5A5A]">{{ $stats['locked_now'] }}</p>
            </div>
            <div class="dgcpt-surface border-[#FF5A5A]/25 p-4 shadow-sm ring-1 ring-[rgba(255,90,90,0.15)] sm:col-span-2 lg:col-span-2">
                <p class="dgcpt-card-title text-[#9FB3C8]">Risques critiques (score ≥ 16)</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-[#E6EEF8]">{{ $risquesCritiques }}</p>
            </div>
            <div class="dgcpt-surface border-[#00D1FF]/25 p-4 shadow-sm ring-1 ring-[rgba(0,209,255,0.2)] sm:col-span-2 lg:col-span-2">
                <p class="dgcpt-card-title">Risques transversaux (ouverts)</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-[#00D1FF]">{{ $crossDepartmentRisksOpen }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="dgcpt-surface p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-[#E6EEF8]">Connexions récentes</h2>
                <ul class="space-y-2 text-sm text-[#E6EEF8]">
                    @forelse ($recentConnected as $u)
                        <li class="flex justify-between gap-2">
                            <span>{{ $u->displayName() }}</span>
                            <span class="whitespace-nowrap text-xs text-[#9FB3C8]">{{ $u->department?->code ?? '—' }} · {{ optional($u->last_login_at)->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-[#9FB3C8]">Aucune connexion enregistrée.</li>
                    @endforelse
                </ul>
            </div>
            <div class="dgcpt-surface p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-[#E6EEF8]">Utilisateurs actifs par département</h2>
                <ul class="max-h-56 space-y-2 overflow-y-auto text-sm">
                    @foreach ($usersByDepartment as $d)
                        <li class="flex justify-between gap-2">
                            <span><span class="font-semibold text-[#00D1FF]">{{ $d->code }}</span> <span class="text-[#9FB3C8]">— {{ \Illuminate\Support\Str::limit($d->name, 42) }}</span></span>
                            <span class="font-semibold text-[#E6EEF8]">{{ $d->users_count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="dgcpt-surface overflow-x-auto p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-[#E6EEF8]">Missions par pôle</h2>
            <table class="dgcpt-table min-w-full">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Département</th>
                        <th>Missions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($missionsByDepartment as $row)
                        <tr>
                            <td class="whitespace-nowrap font-semibold text-[#00D1FF]">{{ $row->department?->code ?? '—' }}</td>
                            <td class="text-[#9FB3C8]">{{ \Illuminate\Support\Str::limit($row->department?->name ?? '—', 48) }}</td>
                            <td class="font-semibold text-[#E6EEF8]">{{ $row->total }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-[#9FB3C8]">Aucune mission rattachée à un département.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="dgcpt-surface p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-[#E6EEF8]">Comptes verrouillés</h2>
                <ul class="space-y-2 text-sm text-[#E6EEF8]">
                    @forelse ($lockedUsers as $u)
                        <li class="flex justify-between gap-2">
                            <span>{{ $u->displayName() }}</span>
                            <span class="whitespace-nowrap text-[#9FB3C8]">jusqu’à {{ $u->locked_until?->format('d/m/Y H:i') }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-[#9FB3C8]">Aucun verrouillage en cours.</li>
                    @endforelse
                </ul>
            </div>
            <div class="dgcpt-surface p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-[#E6EEF8]">Alertes sécurité (récentes)</h2>
                <ul class="space-y-2 text-sm">
                    @forelse ($securityAlerts as $log)
                        <li>
                            <span class="font-semibold text-[#E6EEF8]">{{ $log->action }}</span>
                            <span class="text-[#9FB3C8]"> — {{ \Illuminate\Support\Str::limit($log->description ?? '', 80) }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-[#9FB3C8]">Aucune alerte récente.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="dgcpt-surface overflow-x-auto p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-[#E6EEF8]">Activités administratives / audit (extrait)</h2>
            <table class="dgcpt-table min-w-full">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentAudits as $log)
                        <tr>
                            <td class="whitespace-nowrap text-[#9FB3C8]">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="font-medium text-[#E6EEF8]">{{ $log->action }}</td>
                            <td class="text-[#9FB3C8]">{{ $log->module }}</td>
                            <td class="text-[#9FB3C8]">{{ \Illuminate\Support\Str::limit($log->description ?? '', 120) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
