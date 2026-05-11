<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">
        <div>
            <p class="dgcpt-card-title">IAM</p>
            <h1 class="dgcpt-page-title">Modifier {{ $editUser->displayName() }}</h1>
            <p class="mt-1 text-sm">
                <a href="{{ route('admin.users.index') }}" class="dgcpt-link">← Retour liste</a>
            </p>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-[rgba(0,168,107,0.4)] bg-[#10192B] px-4 py-3 text-sm font-medium text-[#E6EEF8]">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-[rgba(255,90,90,0.45)] bg-[#10192B] px-4 py-3 text-sm text-[#FF5A5A]">
                <ul class="ms-5 list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('admin.users.update', $editUser) }}" class="dgcpt-surface space-y-6 p-6 shadow-sm">
            @csrf
            @method('patch')
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="dgcpt-label">Prénom</label>
                    <input type="text" name="prenom" value="{{ old('prenom', $editUser->prenom) }}" autocomplete="given-name" class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label">Nom <span class="text-[#FF5A5A]">*</span></label>
                    <input type="text" name="nom" value="{{ old('nom', $editUser->name) }}" required autocomplete="family-name" class="dgcpt-input" />
                </div>
            </div>
            <div>
                <label class="dgcpt-label">Email</label>
                <input type="email" name="email" value="{{ old('email', $editUser->email) }}" required class="dgcpt-input" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="dgcpt-label">Département</label>
                    <select name="department_id" class="dgcpt-select">
                        <option value="">—</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}" @selected(old('department_id', $editUser->department_id) == $d->id)>{{ $d->code }} — {{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="dgcpt-label">Catégorie (rôle institutionnel)</label>
                    <select name="role_id" class="dgcpt-select">
                        <option value="">—</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r->id }}" @selected(old('role_id', $editUser->role_id) == $r->id)>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="dgcpt-label">Poste / fonction</label>
                    <input type="text" name="position" value="{{ old('position', $editUser->position) }}" class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label">Matricule</label>
                    <input type="text" name="matricule" value="{{ old('matricule', $editUser->matricule) }}" class="dgcpt-input" />
                </div>
            </div>
            <div>
                <label class="dgcpt-label">Téléphone</label>
                <input type="text" name="telephone" value="{{ old('telephone', $editUser->telephone) }}" class="dgcpt-input" />
            </div>
            <input type="hidden" name="active" value="0" />
            <label class="inline-flex items-center gap-2 text-sm font-medium text-[#9FB3C8]">
                <input type="checkbox" name="active" value="1" class="h-4 w-4 rounded border-[rgba(0,209,255,0.35)] bg-[#050816] text-[#00D1FF] focus:ring-[#00D1FF]" @checked(old('active', $editUser->active)) />
                Compte actif
            </label>
            <div class="flex flex-wrap gap-3 border-t border-[rgba(0,209,255,0.15)] pt-4">
                <button type="submit" class="dgcpt-btn-primary">Enregistrer</button>
                <a href="{{ route('admin.users.index') }}" class="dgcpt-btn-outline">Annuler</a>
            </div>
        </form>

        <div class="rounded-xl border border-[rgba(244,208,0,0.35)] bg-[#10192B] p-4 shadow-sm">
            <p class="mb-2 text-sm font-semibold text-[#F4D000]">Réinitialisation du mot de passe</p>
            <form method="post" action="{{ route('admin.users.password-reset', $editUser) }}">
                @csrf
                <button type="submit" class="dgcpt-btn-outline border-[rgba(244,208,0,0.35)] text-[#E6EEF8] hover:border-[rgba(244,208,0,0.55)]">
                    Envoyer lien de réinitialisation par email
                </button>
            </form>
        </div>

        @if (auth()->id() !== $editUser->id && $editUser->active)
            <form method="post" action="{{ route('admin.users.deactivate', $editUser) }}" class="rounded-xl border border-[rgba(255,90,90,0.45)] bg-[#10192B] p-4"
                  onsubmit="return confirm(&quot;Désactiver l'accès pour cet utilisateur ?&quot;);">
                @csrf
                <button type="submit" class="text-sm font-semibold text-[#FF5A5A] hover:underline">
                    Désactiver cet utilisateur
                </button>
            </form>
        @endif
    </div>
</x-app-layout>
