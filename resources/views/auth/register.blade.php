<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="prenom" value="Prénom" />
            <x-text-input id="prenom" class="block mt-1 w-full" type="text" name="prenom" :value="old('prenom')" required autofocus autocomplete="given-name" />
            <x-input-error :messages="$errors->get('prenom')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="name" value="Nom" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autocomplete="family-name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="telephone" value="Téléphone" />
            <x-text-input id="telephone" class="block mt-1 w-full" type="text" name="telephone" :value="old('telephone')" autocomplete="tel" />
            <x-input-error :messages="$errors->get('telephone')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" value="Email professionnel" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="registration_requested_department_id" value="Département / Pôle demandé" />
            <select id="registration_requested_department_id" name="registration_requested_department_id" required
                    class="block mt-1 w-full rounded-xl border border-white/10 bg-slate-950/60 py-2.5 text-slate-100 focus:border-dgcpt-cyan/50 focus:outline-none focus:ring-2 focus:ring-dgcpt-cyan/30">
                <option value="" disabled {{ old('registration_requested_department_id') ? '' : 'selected' }}>— Choisir —</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" @selected((string) old('registration_requested_department_id') === (string) $dept->id)>
                        {{ $dept->code }} — {{ $dept->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('registration_requested_department_id')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="fonction" value="Poste / Fonction" />
            <x-text-input id="fonction" class="block mt-1 w-full" type="text" name="fonction" :value="old('fonction')" required autocomplete="organization-title" />
            <x-input-error :messages="$errors->get('fonction')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="matricule" value="Matricule" />
            <x-text-input id="matricule" class="block mt-1 w-full" type="text" name="matricule" :value="old('matricule')" autocomplete="off" />
            <x-input-error :messages="$errors->get('matricule')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Mot de passe" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirmation du mot de passe" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="text-sm font-semibold text-dgcpt-cyan underline-offset-4 hover:underline" href="{{ route('login') }}">
                Déjà inscrit ?
            </a>

            <x-primary-button class="ms-4">
                Envoyer la demande
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
