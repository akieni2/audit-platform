<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="dgcpt-card-title">Administration CFDT</p>
                <h1 class="dgcpt-page-title">Habilitations</h1>
                <p class="dgcpt-text-muted">Accordez ou retirez à tout moment un rôle dans l'espace de formation.</p>
            </div>
            <a class="dgcpt-btn-secondary" href="{{ route('cfdt.index') }}">Retour au CFDT</a>
        </div>

        @if(session('status'))<div class="dgcpt-surface p-4">{{ session('status') }}</div>@endif

        <div class="dgcpt-surface overflow-x-auto">
            <table class="min-w-full text-left">
                <thead><tr class="border-b border-slate-700"><th class="p-4">Agent</th><th class="p-4">Structure</th><th class="p-4">Rôle CFDT</th><th class="p-4">Action</th></tr></thead>
                <tbody>
                @foreach($users as $user)
                    <tr class="border-b border-slate-800">
                        <td class="p-4"><strong>{{ $user->prenom }} {{ $user->name }}</strong><br><span class="dgcpt-text-muted">{{ $user->email }}</span></td>
                        <td class="p-4">{{ $user->department?->name ?? 'Non rattaché' }}</td>
                        <td class="p-4">
                            <form class="flex min-w-72 gap-2" method="post" action="{{ route('cfdt.access.update', $user) }}">
                                @csrf @method('PATCH')
                                <select class="dgcpt-select" name="cfdt_role">
                                    <option value="">Aucun accès</option>
                                    <option value="learner" @selected($user->cfdt_role === 'learner')>Apprenant</option>
                                    <option value="trainer" @selected($user->cfdt_role === 'trainer')>Formateur</option>
                                    <option value="validator" @selected($user->cfdt_role === 'validator')>Validateur</option>
                                    <option value="administrator" @selected($user->cfdt_role === 'administrator')>Administrateur CFDT</option>
                                </select>
                                <button class="dgcpt-btn-primary">Enregistrer</button>
                            </form>
                        </td>
                        <td class="p-4 text-sm">Accès révocable</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </div>
</x-app-layout>
