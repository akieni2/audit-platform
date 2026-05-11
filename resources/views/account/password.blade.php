<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-12 sm:px-6 lg:px-8">
        <div class="dgcpt-surface p-4 shadow sm:rounded-xl sm:p-8">
            <div class="max-w-xl">
                <h2 class="text-base font-bold uppercase tracking-wider text-[#E6EEF8]">
                    Changer le mot de passe
                </h2>
                <p class="mb-6 mt-1 text-sm text-[#9FB3C8]">
                    Ancien mot de passe obligatoire, mot de passe fort et confirmation requise.
                </p>
                @include('profile.partials.update-password-form')
            </div>
        </div>
    </div>
</x-app-layout>
