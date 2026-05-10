<x-app-layout>
    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Tableau de bord admin</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pilotage institutionnel des comptes, sécurité et indicateurs d’activité.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('admin.users.create') }}" class="rounded-md bg-slate-700 px-4 py-2 font-semibold text-white shadow hover:bg-slate-600">Créer utilisateur</a>
                <a href="{{ route('admin.users.index') }}" class="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white shadow hover:bg-indigo-500">Utilisateurs</a>
                @can('manageDepartments')
                    <a href="{{ route('admin.departments.index') }}" class="rounded-md bg-slate-600 px-4 py-2 font-semibold text-white shadow hover:bg-slate-500">Pôles / départements</a>
                @endcan
                <a href="{{ route('admin.security.audit-logs') }}" class="rounded-md border border-gray-300 dark:border-gray-600 px-4 py-2 font-semibold text-gray-800 dark:text-gray-200">Journal sécurité</a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Actifs</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-50">{{ $stats['active'] }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Inactifs</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-50">{{ $stats['inactive'] }}</p>
            </div>
            <div class="rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50/50 dark:bg-amber-900/10 p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-amber-800 dark:text-amber-200">Mot de passe à changer</p>
                <p class="mt-2 text-3xl font-bold text-amber-950 dark:text-amber-100">{{ $stats['must_change'] }}</p>
            </div>
            <div class="rounded-lg border border-red-200 dark:border-red-900 bg-red-50/50 dark:bg-red-900/10 p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-red-800 dark:text-red-200">Verrouillés (maintenant)</p>
                <p class="mt-2 text-3xl font-bold text-red-950 dark:text-red-100">{{ $stats['locked_now'] }}</p>
            </div>
            <div class="rounded-lg border border-indigo-200 dark:border-indigo-900 bg-indigo-50/50 dark:bg-indigo-900/10 p-4 shadow-sm sm:col-span-2 lg:col-span-2">
                <p class="text-xs uppercase tracking-wide text-indigo-800 dark:text-indigo-200">Risques critiques (score ≥ 16)</p>
                <p class="mt-2 text-3xl font-bold text-indigo-950 dark:text-indigo-100">{{ $risquesCritiques }}</p>
            </div>
            <div class="rounded-lg border border-violet-200 dark:border-violet-900 bg-violet-50/50 dark:bg-violet-900/10 p-4 shadow-sm sm:col-span-2 lg:col-span-2">
                <p class="text-xs uppercase tracking-wide text-violet-800 dark:text-violet-200">Risques transversaux (ouverts)</p>
                <p class="mt-2 text-3xl font-bold text-violet-950 dark:text-violet-100">{{ $crossDepartmentRisksOpen }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Connexions récentes</h2>
                <ul class="space-y-2 text-sm">
                    @forelse ($recentConnected as $u)
                        <li class="flex justify-between gap-2">
                            <span>{{ $u->displayName() }}</span>
                            <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap text-xs">{{ $u->department?->code ?? '—' }} · {{ optional($u->last_login_at)->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="text-gray-500 text-sm">Aucune connexion enregistrée.</li>
                    @endforelse
                </ul>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Utilisateurs actifs par département</h2>
                <ul class="space-y-2 text-sm max-h-56 overflow-y-auto">
                    @foreach ($usersByDepartment as $d)
                        <li class="flex justify-between gap-2">
                            <span><span class="font-medium">{{ $d->code }}</span> — {{ \Illuminate\Support\Str::limit($d->name, 42) }}</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $d->users_count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm overflow-x-auto">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Missions par pôle</h2>
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-2 pr-4">Code</th>
                        <th class="py-2 pr-4">Département</th>
                        <th class="py-2">Missions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($missionsByDepartment as $row)
                        <tr>
                            <td class="py-2 pr-4 whitespace-nowrap font-medium">{{ $row->department?->code ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ \Illuminate\Support\Str::limit($row->department?->name ?? '—', 48) }}</td>
                            <td class="py-2">{{ $row->total }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-3 text-gray-500">Aucune mission rattachée à un département.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Comptes verrouillés</h2>
                <ul class="space-y-2 text-sm">
                    @forelse ($lockedUsers as $u)
                        <li class="flex justify-between gap-2">
                            <span>{{ $u->displayName() }}</span>
                            <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap">jusqu’à {{ $u->locked_until?->format('d/m/Y H:i') }}</span>
                        </li>
                    @empty
                        <li class="text-gray-500 text-sm">Aucun verrouillage en cours.</li>
                    @endforelse
                </ul>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Alertes sécurité (récentes)</h2>
                <ul class="space-y-2 text-sm">
                    @forelse ($securityAlerts as $log)
                        <li>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $log->action }}</span>
                            <span class="text-gray-500 dark:text-gray-400"> — {{ \Illuminate\Support\Str::limit($log->description ?? '', 80) }}</span>
                        </li>
                    @empty
                        <li class="text-gray-500 text-sm">Aucune alerte récente.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm overflow-x-auto">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Activités administratives / audit (extrait)</h2>
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-2 pr-4">Date</th>
                        <th class="py-2 pr-4">Action</th>
                        <th class="py-2 pr-4">Module</th>
                        <th class="py-2">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($recentAudits as $log)
                        <tr>
                            <td class="py-2 pr-4 whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="py-2 pr-4">{{ $log->action }}</td>
                            <td class="py-2 pr-4">{{ $log->module }}</td>
                            <td class="py-2">{{ \Illuminate\Support\Str::limit($log->description ?? '', 120) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
