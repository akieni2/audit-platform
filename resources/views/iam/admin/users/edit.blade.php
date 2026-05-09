<x-app-layout>
    <div class="py-10 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Modifier {{ $editUser->displayName() }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                <a href="{{ route('admin.users.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">← Retour liste</a>
            </p>
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

        <form method="post" action="{{ route('admin.users.update', $editUser) }}" class="space-y-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 shadow-sm">
            @csrf
            @method('patch')
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prénom</label>
                    <input type="text" name="prenom" value="{{ old('prenom', $editUser->prenom) }}" autocomplete="given-name"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom <span class="text-red-600">*</span></label>
                    <input type="text" name="nom" value="{{ old('nom', $editUser->name) }}" required autocomplete="family-name"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input type="email" name="email" value="{{ old('email', $editUser->email) }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Département</label>
                    <select name="department_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm">
                        <option value="">—</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}" @selected(old('department_id', $editUser->department_id) == $d->id)>{{ $d->code }} — {{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Catégorie (rôle institutionnel)</label>
                    <select name="role_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm">
                        <option value="">—</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r->id }}" @selected(old('role_id', $editUser->role_id) == $r->id)>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Poste / fonction</label>
                    <input type="text" name="position" value="{{ old('position', $editUser->position) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Matricule</label>
                    <input type="text" name="matricule" value="{{ old('matricule', $editUser->matricule) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Téléphone</label>
                <input type="text" name="telephone" value="{{ old('telephone', $editUser->telephone) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
            </div>
            <input type="hidden" name="active" value="0" />
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="active" value="1" @checked(old('active', $editUser->active)) />
                Compte actif
            </label>
            <div>
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">
                    Enregistrer
                </button>
            </div>
        </form>

        <div class="rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50/50 dark:bg-amber-900/10 p-4">
            <p class="text-sm text-amber-900 dark:text-amber-100 mb-2 font-medium">Réinitialisation du mot de passe</p>
            <form method="post" action="{{ route('admin.users.password-reset', $editUser) }}">
                @csrf
                <button type="submit" class="rounded-md border border-amber-600 text-amber-900 dark:text-amber-100 px-4 py-2 text-sm font-semibold">
                    Envoyer lien de réinitialisation par email
                </button>
            </form>
        </div>

        @if (auth()->id() !== $editUser->id && $editUser->active)
            <form method="post" action="{{ route('admin.users.deactivate', $editUser) }}" class="border border-red-200 dark:border-red-900 rounded-lg p-4 bg-red-50/50 dark:bg-red-900/10"
                  onsubmit="return confirm('Désactiver l’accès pour cet utilisateur ?');">
                @csrf
                <button type="submit" class="text-sm font-semibold text-red-700 dark:text-red-300 hover:underline">
                    Désactiver cet utilisateur
                </button>
            </form>
        @endif
    </div>
</x-app-layout>
