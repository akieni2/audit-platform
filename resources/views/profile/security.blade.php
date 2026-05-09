<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Sécurité du compte</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Statut, expiration du mot de passe (rotation institutionnelle) et accès rapides.
                </p>
                <dl class="mt-6 grid gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="font-medium text-gray-700 dark:text-gray-300">Changement obligatoire</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $user->must_change_password ? 'Oui — connectez-vous via l’écran dédié' : 'Non' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-700 dark:text-gray-300">Dernière modification du mot de passe</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $user->password_changed_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-700 dark:text-gray-300">Expiration prévue (rotation {{ config('dgcpt.password_rotation_days', 90) }} jours)</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $user->password_expires_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-700 dark:text-gray-300">Dernière connexion</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('account.password') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">
                        Changer le mot de passe
                    </a>
                    <a href="{{ route('profile.edit') }}" class="inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-semibold text-gray-800 dark:text-gray-200">
                        Mon profil
                    </a>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Historique connexions &amp; sécurité</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 mb-4">Événements récents issus du journal d’audit pour votre compte.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                <th class="py-2 pr-4">Date</th>
                                <th class="py-2 pr-4">Action</th>
                                <th class="py-2">Détail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($loginHistory as $row)
                                <tr>
                                    <td class="py-2 pr-4 whitespace-nowrap">{{ $row->created_at?->format('d/m/Y H:i:s') }}</td>
                                    <td class="py-2 pr-4 font-medium">{{ $row->action }}</td>
                                    <td class="py-2 text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($row->description ?? '', 120) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-4 text-gray-500">Aucun événement enregistré.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
