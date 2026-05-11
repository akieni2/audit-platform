<x-app-layout>
    <div class="mx-auto max-w-lg px-4 py-12 sm:px-6 lg:px-8">
        <div class="dgcpt-surface border border-[rgba(244,208,0,0.35)] p-6 shadow sm:rounded-xl sm:p-8">
            <div class="mb-6">
                <h1 class="text-xl font-bold uppercase tracking-wide text-[#E6EEF8]">
                    Changement de mot de passe obligatoire
                </h1>
                <p class="mt-2 text-sm text-[#9FB3C8]">
                    Définissez un mot de passe fort conforme à la politique DGCPT (min. 12 caractères, complexité requise).
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-[rgba(255,90,90,0.45)] bg-[#10192B] px-4 py-3 text-sm text-[#FF5A5A]">
                    <ul class="ms-5 list-disc space-y-1">
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

            <form method="POST" action="{{ route('logout') }}" class="mt-6 border-t border-[rgba(0,209,255,0.15)] pt-6">
                @csrf
                <button type="submit" class="w-full text-center text-sm font-semibold text-[#9FB3C8] transition hover:text-[#00D1FF] hover:underline">
                    Déconnexion
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
