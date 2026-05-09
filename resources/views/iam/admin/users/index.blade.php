<x-app-layout>
    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Administration — utilisateurs</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Création, affectation département / rôle institutionnel, désactivation et réinitialisation sécurisée.
                </p>
            </div>
            <a href="{{ route('admin.users.create') }}"
               class="inline-flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">
                Nouvel utilisateur
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-800 dark:text-green-200 border border-green-200 dark:border-green-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-md bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800">
                <ul class="list-disc ms-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Actifs</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-50">{{ $stats['active'] }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Désactivés</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-50">{{ $stats['inactive'] }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm sm:col-span-2">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Connexions récentes</p>
                <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                    @forelse ($recentLogins as $u)
                        <li class="flex justify-between gap-2">
                            <span>{{ $u->name }}</span>
                            <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ optional($u->last_login_at)->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="text-gray-500">Aucune connexion enregistrée.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Par département (actifs)</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($byDepartment as $d)
                        <li class="flex justify-between">
                            <span><span class="inline-flex items-center rounded-full bg-slate-100 dark:bg-slate-700 px-2 py-0.5 text-xs font-medium text-slate-800 dark:text-slate-100">{{ $d->code }}</span> {{ $d->name }}</span>
                            <span class="font-medium">{{ $d->users_count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Par rôle institutionnel (actifs)</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($byRole as $r)
                        <li class="flex justify-between">
                            <span>{{ $r->name }}</span>
                            <span class="font-medium">{{ $r->users_count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <form method="get" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-3 items-end bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Recherche</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, email, matricule…"
                       class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Département</label>
                <select name="department_id" class="block rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm">
                    <option value="">— Tous —</option>
                    @foreach ($departments as $d)
                        <option value="{{ $d->id }}" @selected(($filters['department_id'] ?? '') == $d->id)>{{ $d->code }} — {{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="inactive_only" value="1" @checked(!empty($filters['inactive_only'])) />
                Désactivés seulement
            </label>
            <button type="submit" class="rounded-md bg-slate-800 dark:bg-slate-600 px-4 py-2 text-sm font-semibold text-white">Filtrer</button>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 py-2">Réinitialiser</a>
        </form>

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Utilisateur</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Département</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Rôle</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Statut</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($users as $u)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $u->name }}</div>
                                <div class="text-xs text-gray-500">{{ $u->email }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($u->department)
                                    <span class="inline-flex rounded-full bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 text-xs font-medium text-blue-800 dark:text-blue-200">{{ $u->department->code }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($u->institutionalRole)
                                    {{ $u->institutionalRole->name }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($u->active)
                                    <span class="inline-flex rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:text-emerald-200">Actif</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-700 px-2 py-0.5 text-xs font-medium text-slate-700 dark:text-slate-200">Désactivé</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('admin.users.edit', $u) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-semibold">Modifier</a>
                                @if ($u->active && auth()->id() !== $u->id)
                                    <form method="post" action="{{ route('admin.users.deactivate', $u) }}" class="inline" onsubmit="return confirm('Désactiver cet utilisateur ?');">
                                        @csrf
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-xs font-semibold">Désactiver</button>
                                    </form>
                                @endif
                                <form method="post" action="{{ route('admin.users.password-reset', $u) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-slate-600 dark:text-slate-300 hover:underline text-xs font-semibold">Reset MDP</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
