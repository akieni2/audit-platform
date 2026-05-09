<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                        Changer le mot de passe
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Ancien mot de passe obligatoire, mot de passe fort et confirmation requise.
                    </p>
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
