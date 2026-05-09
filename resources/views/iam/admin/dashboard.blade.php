<x-app-layout>
    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Administration centrale</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pilotage des comptes, sécurité et activité récente.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('admin.users.create') }}" class="rounded-md bg-emerald-600 px-4 py-2 font-semibold text-white shadow hover:bg-emerald-500">Créer un utilisateur</a>
                <a href="{{ route('admin.users.index') }}" class="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white shadow hover:bg-indigo-500">Liste utilisateurs</a>
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
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Comptes verrouillés</h2>
                <ul class="space-y-2 text-sm">
                    @forelse ($lockedUsers as $u)
                        <li class="flex justify-between gap-2">
                            <span>{{ $u->name }}</span>
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
