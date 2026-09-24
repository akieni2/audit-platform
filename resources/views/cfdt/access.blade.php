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
        @if($errors->any())<div class="dgcpt-surface border border-red-500 p-4 text-red-200"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <details class="dgcpt-surface p-5">
            <summary class="cursor-pointer text-lg font-black">Créer un compte formateur ou agent</summary>
            <p class="mt-2 dgcpt-text-muted">Le compte est rattaché à l’organigramme. L’utilisateur reçoit un lien temporaire pour définir son mot de passe.</p>
            <form class="mt-4 grid gap-3 md:grid-cols-2" method="post" action="{{ route('cfdt.access.store') }}">
                @csrf
                <input class="dgcpt-input" name="name" value="{{ old('name') }}" placeholder="Nom" required>
                <input class="dgcpt-input" name="prenom" value="{{ old('prenom') }}" placeholder="Prénom">
                <input class="dgcpt-input" type="email" name="email" value="{{ old('email') }}" placeholder="Courriel professionnel" required>
                <input class="dgcpt-input" name="fonction" value="{{ old('fonction') }}" placeholder="Fonction / titre">
                <select class="dgcpt-select" name="department_id" required><option value="">Structure dans l’organigramme</option>@foreach($departments as $department)<option value="{{$department->id}}" @selected(old('department_id')==$department->id)>{{$department->code}} — {{$department->name}}</option>@endforeach</select>
                <select class="dgcpt-select" name="role" required><option value="agent_operationnel">Agent opérationnel</option><option value="inspecteur_verificateur">Inspecteur vérificateur</option><option value="inspecteur_verificateur_adjoint">Inspecteur vérificateur adjoint</option><option value="chef_service">Chef de service</option><option value="directeur">Directeur</option><option value="directeur_adjoint">Directeur adjoint</option><option value="manager">Responsable</option></select>
                <select class="dgcpt-select" name="cfdt_role" required><option value="learner">Apprenant</option><option value="trainer">Formateur</option><option value="validator">Validateur</option>@if(auth()->user()->isInstitutionalSuperAdmin())<option value="administrator">Administrateur CFDT</option>@endif</select>
                <div><button class="dgcpt-btn-primary">Créer et inviter</button></div>
            </form>
        </details>

        <div class="dgcpt-surface overflow-x-auto">
            <table class="min-w-full text-left">
                <thead><tr class="border-b border-slate-700"><th class="p-4">Agent</th><th class="p-4">Structure</th><th class="p-4">Accès actuel</th><th class="p-4">Action</th></tr></thead>
                <tbody>
                @foreach($users as $user)
                    @php
                        $cfdtRoleLabels = [
                            'learner' => 'Agent / apprenant',
                            'trainer' => 'Formateur / enseignant',
                            'validator' => 'Validateur pédagogique',
                            'administrator' => 'Administrateur CFDT',
                        ];
                    @endphp
                    <tr class="border-b border-slate-800">
                        <td class="p-4"><strong>{{ $user->prenom }} {{ $user->name }}</strong><br><span class="dgcpt-text-muted">{{ $user->email }}</span></td>
                        <td class="p-4">{{ $user->department?->name ?? 'Non rattaché' }}</td>
                        <td class="p-4">
                            <span class="inline-flex rounded-full border border-cyan-900 bg-cyan-950/30 px-3 py-1 text-sm font-semibold text-cyan-100">
                                {{ $cfdtRoleLabels[$user->cfdt_role] ?? 'Aucun accès au CFDT' }}
                            </span>
                        </td>
                        <td class="p-4 align-top">
                            @if($user->isInstitutionalSuperAdmin())
                                <span class="text-sm text-[#9FB3C8]">Compte Super Admin protégé</span>
                            @else
                                <details class="group min-w-72">
                                    <summary class="dgcpt-btn-primary inline-flex cursor-pointer list-none items-center gap-2">
                                        {{ $user->cfdt_role ? 'Modifier le rôle' : 'Affecter au CFDT' }}
                                        <span class="transition group-open:rotate-180">⌄</span>
                                    </summary>
                                    <form class="mt-3 space-y-3 rounded-xl border border-cyan-900/70 bg-slate-950/70 p-4" method="post" action="{{ route('cfdt.access.update', $user) }}">
                                        @csrf @method('PATCH')
                                        <p class="text-sm font-bold text-[#E6EEF8]">Choisir le rôle de {{ $user->prenom ?: $user->name }}</p>
                                        <div class="grid gap-2">
                                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-700 p-3 hover:border-cyan-600">
                                                <input type="radio" name="cfdt_role" value="learner" @checked($user->cfdt_role === 'learner')>
                                                <span><strong>Agent / apprenant</strong><small class="block text-[#9FB3C8]">Suit les formations, passe les tests et reçoit ses certificats.</small></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-700 p-3 hover:border-cyan-600">
                                                <input type="radio" name="cfdt_role" value="trainer" @checked($user->cfdt_role === 'trainer')>
                                                <span><strong>Formateur / enseignant</strong><small class="block text-[#9FB3C8]">Crée les contenus, QCM et affectations pédagogiques.</small></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-700 p-3 hover:border-cyan-600">
                                                <input type="radio" name="cfdt_role" value="validator" @checked($user->cfdt_role === 'validator')>
                                                <span><strong>Validateur pédagogique</strong><small class="block text-[#9FB3C8]">Contrôle et publie les formations préparées.</small></span>
                                            </label>
                                            @if(auth()->user()->isInstitutionalSuperAdmin() || $user->cfdt_role === 'administrator')
                                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-700 p-3 hover:border-cyan-600">
                                                    <input type="radio" name="cfdt_role" value="administrator" @checked($user->cfdt_role === 'administrator')>
                                                    <span><strong>Administrateur CFDT</strong><small class="block text-[#9FB3C8]">Administre les comptes, les formations et les affectations.</small></span>
                                                </label>
                                            @endif
                                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-red-900/60 p-3 hover:border-red-600">
                                                <input type="radio" name="cfdt_role" value="" @checked(!$user->cfdt_role)>
                                                <span><strong>Retirer l’accès</strong><small class="block text-[#9FB3C8]">L’utilisateur reste dans la base mais ne voit plus le CFDT.</small></span>
                                            </label>
                                        </div>
                                        <button class="dgcpt-btn-primary w-full">Confirmer l’affectation</button>
                                    </form>
                                </details>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </div>
</x-app-layout>
