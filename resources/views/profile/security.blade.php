<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-12 sm:px-6 lg:px-8">
        <div class="dgcpt-surface p-4 shadow sm:rounded-xl sm:p-8">
            <h2 class="text-base font-bold uppercase tracking-wider text-[#E6EEF8]">Sécurité du compte</h2>
            <p class="mt-1 text-sm text-[#9FB3C8]">
                Statut, expiration du mot de passe (rotation institutionnelle) et accès rapides.
            </p>
            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="dgcpt-card-title">Changement obligatoire</dt>
                    <dd class="mt-1 text-[#E6EEF8]">{{ $user->must_change_password ? "Oui — connectez-vous via l'écran dédié" : 'Non' }}</dd>
                </div>
                <div>
                    <dt class="dgcpt-card-title">Dernière modification du mot de passe</dt>
                    <dd class="mt-1 text-[#E6EEF8]">{{ $user->password_changed_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="dgcpt-card-title">Expiration prévue (rotation {{ config('dgcpt.password_rotation_days', 90) }} jours)</dt>
                    <dd class="mt-1 text-[#E6EEF8]">{{ $user->password_expires_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="dgcpt-card-title">Dernière connexion</dt>
                    <dd class="mt-1 text-[#E6EEF8]">{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
            </dl>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('account.password') }}" class="dgcpt-btn-primary">Changer le mot de passe</a>
                <a href="{{ route('profile.edit') }}" class="dgcpt-btn-outline">Mon profil</a>
            </div>
        </div>

        <div class="dgcpt-surface p-4 shadow sm:rounded-xl sm:p-8">
            <h2 class="text-base font-bold uppercase tracking-wider text-[#E6EEF8]">Historique connexions &amp; sécurité</h2>
            <p class="mb-4 mt-1 text-sm text-[#9FB3C8]">Événements récents issus du journal d'audit pour votre compte.</p>
            <div class="dgcpt-table-wrap shadow-sm">
                <table class="dgcpt-table min-w-full">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Détail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loginHistory as $row)
                            <tr>
                                <td class="whitespace-nowrap">{{ $row->created_at?->format('d/m/Y H:i:s') }}</td>
                                <td class="font-semibold">{{ $row->action }}</td>
                                <td class="text-[#9FB3C8]">{{ \Illuminate\Support\Str::limit($row->description ?? '', 120) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-[#9FB3C8]">Aucun événement enregistré.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
