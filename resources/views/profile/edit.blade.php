<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl space-y-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Identité institutionnelle</h3>
                    <dl class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <div><span class="font-medium text-gray-800 dark:text-gray-200">Département :</span>
                            @if ($user->department)
                                <span class="inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 text-xs font-medium text-blue-800 dark:text-blue-200">{{ $user->department->code }}</span>
                                {{ $user->department->name }}
                            @else
                                —
                            @endif
                        </div>
                        <div><span class="font-medium text-gray-800 dark:text-gray-200">Rôle :</span>
                            {{ $user->institutionalRole?->name ?? '—' }}
                        </div>
                    </dl>
                </div>
            </div>
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
