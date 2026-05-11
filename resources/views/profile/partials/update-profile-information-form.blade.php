<section>
    <header>
        <h2 class="text-base font-bold uppercase tracking-wider text-[#E6EEF8]">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-[#9FB3C8]">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        @if ($user->profile_photo)
            <div class="flex items-center gap-3">
                <img src="{{ asset('storage/'.$user->profile_photo) }}" alt="" class="h-14 w-14 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-600" />
                <p class="text-xs text-[#9FB3C8]">Photo actuelle</p>
            </div>
        @endif

        <div>
            <x-input-label for="profile_photo" value="Photo de profil" />
            <input id="profile_photo" name="profile_photo" type="file" accept="image/*"
                   class="mt-1 block w-full text-sm text-[#9FB3C8] file:mr-4 file:rounded-lg file:border-0 file:bg-[#10192B] file:px-3 file:py-2 file:text-[#E6EEF8] file:ring-1 file:ring-[rgba(0,209,255,0.2)]" />
            <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="telephone" value="Téléphone" />
            <x-text-input id="telephone" name="telephone" type="text" class="mt-1 block w-full" :value="old('telephone', $user->telephone)" autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('telephone')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="rounded-md text-sm font-semibold text-[#00D1FF] underline-offset-2 hover:underline focus:outline-none focus:ring-2 focus:ring-[#00D1FF] focus:ring-offset-2 focus:ring-offset-[#0B1220]">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
