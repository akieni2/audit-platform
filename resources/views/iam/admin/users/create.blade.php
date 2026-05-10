<x-app-layout>
    <div class="py-10 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <header class="space-y-1">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Nouvel utilisateur</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Affectez un <strong>département / pôle</strong> pour l'isolation des données et le tableau de bord.
            </p>
            <p>
                <a href="{{ route('admin.users.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Retour à la liste</a>
            </p>
        </header>

        @if ($errors->any())
            <div
                role="alert"
                class="rounded-md border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-800 dark:text-red-200"
            >
                <p class="font-semibold">Des erreurs empêchent l'enregistrement</p>
                <ul class="mt-2 list-disc ps-5 space-y-1">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($departments->isEmpty() || $roles->isEmpty())
            <div
                role="status"
                class="rounded-md border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-900 dark:text-amber-100"
            >
                <p class="font-medium">Configuration IAM incomplète</p>
                <p class="mt-1">
                    Il doit exister au moins un <strong>département actif</strong> et un <strong>rôle institutionnel</strong> pour créer un utilisateur.
                </p>
                @if ($departments->isEmpty() && Route::has('admin.departments.create'))
                    <p class="mt-2">
                        <a href="{{ route('admin.departments.create') }}" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                            Créer un département
                        </a>
                    </p>
                @endif
            </div>
        @endif

        <form
            id="admin-user-create-form"
            action="{{ route('admin.users.store') }}"
            method="post"
            accept-charset="UTF-8"
            class="space-y-6 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm"
        >
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="user-prenom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prénom</label>
                    <input
                        id="user-prenom"
                        type="text"
                        name="prenom"
                        value="{{ old('prenom') }}"
                        autocomplete="given-name"
                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('prenom') border-red-500 ring-1 ring-red-500 @enderror"
                    />
                    @error('prenom')
                        <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="user-nom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nom <span class="text-red-600" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="user-nom"
                        type="text"
                        name="nom"
                        value="{{ old('nom') }}"
                        autocomplete="family-name"
                        required
                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('nom') border-red-500 ring-1 ring-red-500 @enderror"
                    />
                    @error('nom')
                        <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="user-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Email <span class="text-red-600" aria-hidden="true">*</span>
                </label>
                <input
                    id="user-email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="username"
                    required
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('email') border-red-500 ring-1 ring-red-500 @enderror"
                />
                @error('email')
                    <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="user-telephone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Téléphone</label>
                <input
                    id="user-telephone"
                    type="text"
                    name="telephone"
                    value="{{ old('telephone') }}"
                    autocomplete="tel"
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('telephone') border-red-500 ring-1 ring-red-500 @enderror"
                />
                @error('telephone')
                    <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:border-gray-600 dark:bg-gray-900/40 dark:text-gray-400">
                <p class="font-medium text-gray-800 dark:text-gray-200">Exigences du mot de passe (politique DGCPT)</p>
                <ul class="mt-1.5 list-disc ps-4 space-y-0.5">
                    <li>Au moins <strong>12 caractères</strong></li>
                    <li>Au moins une <strong>majuscule</strong> et une <strong>minuscule</strong></li>
                    <li>Au moins un <strong>chiffre</strong></li>
                    <li>Au moins un <strong>caractère spécial</strong> (symbole ou ponctuation)</li>
                    @if (config('dgcpt.password_uncompromised'))
                        <li>Ne doit pas figurer dans les bases de mots de passe compromis (vérification en ligne)</li>
                    @endif
                </ul>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="user-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Mot de passe <span class="text-red-600" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="user-password"
                        type="password"
                        name="password"
                        value="{{ old('password') }}"
                        autocomplete="new-password"
                        required
                        aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('password') border-red-500 ring-1 ring-red-500 @enderror"
                    />
                    @error('password')
                        <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="user-password-confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Confirmation <span class="text-red-600" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="user-password-confirmation"
                        type="password"
                        name="password_confirmation"
                        value="{{ old('password_confirmation') }}"
                        autocomplete="new-password"
                        required
                        aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}"
                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('password_confirmation') border-red-500 ring-1 ring-red-500 @enderror"
                    />
                    @error('password_confirmation')
                        <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="user-department-id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Département / pôle <span class="text-red-600" aria-hidden="true">*</span>
                    </label>
                    <p id="user-department-hint" class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Obligatoire pour le périmètre métier et le bandeau d'accueil.
                    </p>
                    <select
                        id="user-department-id"
                        name="department_id"
                        @if ($departments->isNotEmpty()) required @endif
                        aria-describedby="user-department-hint"
                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('department_id') border-red-500 ring-1 ring-red-500 @enderror"
                    >
                        <option value="" @selected(old('department_id') === null || old('department_id') === '')>— Choisir un département —</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}" @selected((string) old('department_id') === (string) $d->id)>
                                {{ $d->code }} — {{ $d->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('department_id')
                        <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="user-role-id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Catégorie <span class="text-red-600" aria-hidden="true">*</span>
                    </label>
                    <p id="user-role-hint" class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Rôle institutionnel (droits et périmètre).</p>
                    <select
                        id="user-role-id"
                        name="role_id"
                        @if ($roles->isNotEmpty()) required @endif
                        aria-describedby="user-role-hint"
                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('role_id') border-red-500 ring-1 ring-red-500 @enderror"
                    >
                        <option value="" @selected(old('role_id') === null || old('role_id') === '')>— Choisir une catégorie —</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r->id }}" @selected((string) old('role_id') === (string) $r->id)>
                                {{ $r->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="user-position" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Poste / fonction</label>
                    <input
                        id="user-position"
                        type="text"
                        name="position"
                        value="{{ old('position') }}"
                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('position') border-red-500 ring-1 ring-red-500 @enderror"
                    />
                    @error('position')
                        <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="user-matricule" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Matricule</label>
                    <input
                        id="user-matricule"
                        type="text"
                        name="matricule"
                        value="{{ old('matricule') }}"
                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('matricule') border-red-500 ring-1 ring-red-500 @enderror"
                    />
                    @error('matricule')
                        <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <input type="hidden" name="active" value="0" />
                <div class="flex items-start gap-2">
                    <input
                        id="user-active"
                        type="checkbox"
                        name="active"
                        value="1"
                        class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm dark:border-gray-600 dark:bg-gray-900"
                        @checked(old('active', '1') === '1')
                    />
                    <label for="user-active" class="text-sm text-gray-700 dark:text-gray-300">Compte actif</label>
                </div>
                @error('active')
                    <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                <button
                    type="submit"
                    @if ($departments->isEmpty() || $roles->isEmpty()) disabled @endif
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Créer
                </button>
                <a
                    href="{{ route('admin.users.index') }}"
                    class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    Annuler
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
