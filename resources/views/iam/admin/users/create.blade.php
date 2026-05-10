<x-app-layout>
    <div class="py-10 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Nouvel utilisateur</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Affectez un <strong>département / pôle</strong> pour l’isolation des données et le tableau de bord.
                <a href="{{ route('admin.users.index') }}" class="block mt-2 text-indigo-600 dark:text-indigo-400 hover:underline">← Retour liste</a>
            </p>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-md bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800">
                <p class="font-semibold">Des erreurs empêchent l’enregistrement</p>
                <ul class="mt-2 list-disc ms-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($departments->isEmpty() || $roles->isEmpty())
            <div class="rounded-md bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-900 dark:text-amber-100 border border-amber-200 dark:border-amber-800">
                <p class="font-medium">Configuration IAM incomplète</p>
                <p class="mt-1">Il doit exister au moins un <strong>département actif</strong> et un <strong>rôle institutionnel</strong> pour créer un utilisateur.</p>
                @if ($departments->isEmpty() && Route::has('admin.departments.create'))
                    <p class="mt-2"><a href="{{ route('admin.departments.create') }}" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Créer un département</a></p>
                @endif
            </div>
        @endif

        {{-- Formulaire explicite : la sidebar contient un autre <form> (logout) en premier dans le DOM — id + attribut form sur le bouton évitent toute ambiguïté. --}}
        <form
            id="admin-user-create-form"
            method="post"
            action="{{ route('admin.users.store') }}"
            class="space-y-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 shadow-sm"
        >
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prénom</label>
                    <input type="text" name="prenom" value="{{ old('prenom') }}" autocomplete="given-name"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom <span class="text-red-600">*</span></label>
                    <input type="text" name="nom" value="{{ old('nom') }}" required autocomplete="family-name"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email <span class="text-red-600">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Téléphone</label>
                <input type="text" name="telephone" value="{{ old('telephone') }}" autocomplete="tel"
                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2 rounded-md border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 px-3 py-2 text-xs text-gray-600 dark:text-gray-400">
                    <p class="font-medium text-gray-800 dark:text-gray-200">Exigences du mot de passe (politique DGCPT)</p>
                    <ul class="mt-1.5 list-disc ms-4 space-y-0.5">
                        <li>Au moins <strong>12 caractères</strong></li>
                        <li>Au moins une <strong>majuscule</strong> et une <strong>minuscule</strong></li>
                        <li>Au moins un <strong>chiffre</strong></li>
                        <li>Au moins un <strong>caractère spécial</strong> (symbole ou ponctuation)</li>
                        @if (config('dgcpt.password_uncompromised'))
                            <li>Ne doit pas figurer dans les bases de mots de passe compromis (vérification en ligne)</li>
                        @endif
                    </ul>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mot de passe <span class="text-red-600">*</span></label>
                    <input type="password" name="password" required autocomplete="new-password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm @error('password') border-red-500 ring-1 ring-red-500 @enderror" />
                    @error('password')
                        <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmation <span class="text-red-600">*</span></label>
                    <input type="password" name="password_confirmation" required autocomplete="new-password" aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm @error('password_confirmation') border-red-500 ring-1 ring-red-500 @enderror" />
                    @error('password_confirmation')
                        <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Département / pôle <span class="text-red-600">*</span></label>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Obligatoire pour le périmètre métier et le bandeau d’accueil.</p>
                    <select name="department_id" @if($departments->isNotEmpty()) required @endif class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm">
                        <option value="">— Choisir un département —</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}" @selected(old('department_id') == $d->id)>{{ $d->code }} — {{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Catégorie <span class="text-red-600">*</span></label>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Rôle institutionnel (droits et périmètre).</p>
                    <select name="role_id" @if($roles->isNotEmpty()) required @endif class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm">
                        <option value="">— Choisir une catégorie —</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r->id }}" @selected(old('role_id') == $r->id)>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Poste / fonction</label>
                    <input type="text" name="position" value="{{ old('position') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Matricule</label>
                    <input type="text" name="matricule" value="{{ old('matricule') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
                </div>
            </div>

            <input type="hidden" name="active" value="0" />
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="active" value="1" @checked(old('active', true)) />
                Compte actif
            </label>

            <div class="flex gap-3 pt-2">
                <button
                    type="submit"
                    form="admin-user-create-form"
                    @if($departments->isEmpty() || $roles->isEmpty()) disabled @endif
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Créer
                </button>
                <a href="{{ route('admin.users.index') }}" class="rounded-md border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 inline-flex items-center">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
