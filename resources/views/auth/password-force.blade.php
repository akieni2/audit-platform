<x-app-layout>
    <div class="py-12">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="p-6 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg border border-amber-200 dark:border-amber-900">
                <div class="mb-6">
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                        Changement de mot de passe obligatoire
                    </h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Définissez un mot de passe fort conforme à la politique DGCPT (min. 12 caractères, complexité requise).
                    </p>
                </div>

                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800">
                        <ul class="list-disc ms-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.force.update') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="current_password" value="Mot de passe actuel" />
                        <x-text-input id="current_password" name="current_password" type="password" class="mt-1 block w-full" required autocomplete="current-password" />
                        <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" value="Nouveau mot de passe" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Confirmation" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <div class="flex flex-col gap-3 pt-2">
                        <x-primary-button class="w-full justify-center">
                            Valider et continuer
                        </x-primary-button>
                    </div>
                </form>

                <form method="POST" action="{{ route('logout') }}" class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                    @csrf
                    <button type="submit" class="text-sm text-gray-600 dark:text-gray-400 hover:underline w-full text-center">
                        Déconnexion
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
