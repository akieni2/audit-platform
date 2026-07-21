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

        @include('iam.admin.users.partials.temporary-password')

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
                <div>
                    <label class="dgcpt-label">Intercom <span class="font-normal text-[#9FB3C8]">(facultatif)</span></label>
                    <input type="text" name="intercom" value="{{ old('intercom', $editUser->intercom) }}" placeholder="Ex. 53018" class="dgcpt-input" />
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
            @if (config('mail.default') === 'array')
                @if (auth()->id() !== $editUser->id)
                    <form method="post" action="{{ route('admin.users.temporary-password', $editUser) }}" onsubmit="return confirm('Générer un nouveau mot de passe temporaire et déconnecter cet utilisateur ?');">
                        @csrf
                        <button type="submit" class="dgcpt-btn-outline border-[rgba(244,208,0,0.35)] text-[#E6EEF8] hover:border-[rgba(244,208,0,0.55)]">
                            Générer un mot de passe temporaire
                        </button>
                    </form>
                @else
                    <p class="text-sm text-[#9FB3C8]">Utilisez la rubrique Sécurité du compte pour modifier votre propre mot de passe.</p>
                @endif
            @else
                <form method="post" action="{{ route('admin.users.password-reset', $editUser) }}">
                    @csrf
                    <button type="submit" class="dgcpt-btn-outline border-[rgba(244,208,0,0.35)] text-[#E6EEF8] hover:border-[rgba(244,208,0,0.55)]">
                        Envoyer le lien de réinitialisation par courriel
                    </button>
                </form>
            @endif
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
        @can('deleteFromAdministration', $editUser)
            @if (auth()->id() !== $editUser->id && ! $editUser->trashed())
                <form method="post" action="{{ route('admin.users.destroy', $editUser) }}" class="rounded-xl border border-[rgba(255,90,90,0.45)] bg-[#10192B] p-4"
                      onsubmit="return confirm(&quot;Supprimer cet utilisateur ? Son accès sera révoqué, mais les traces institutionnelles seront conservées.&quot;);">
                    @csrf
                    @method('delete')
                    <p class="mb-2 text-sm font-semibold text-[#FF5A5A]">Suppression IAM</p>
                    <button type="submit" class="inline-flex items-center gap-2 text-sm font-semibold text-[#FF5A5A] hover:underline">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m2 0v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6h12zM10 11v6M14 11v6"/></svg>
                        Supprimer cet utilisateur
                    </button>
                    <p class="mt-2 text-xs text-[#9FB3C8]">Suppression logique: les missions, risques, journaux et traces restent conservés.</p>
                </form>
            @endif
        @endcan
    </div>
</x-app-layout>
