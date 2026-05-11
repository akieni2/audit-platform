<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-0 py-4 sm:px-0">
        <div>
            <p class="dgcpt-card-title">Compte</p>
            <h1 class="dgcpt-page-title">Profil</h1>
        </div>

        <div class="dgcpt-surface p-6 shadow-sm sm:p-8">
            <div class="max-w-xl space-y-3">
                <h3 class="text-base font-bold uppercase tracking-wider text-[#E6EEF8]">Identité institutionnelle</h3>
                <dl class="space-y-1 text-sm text-[#9FB3C8]">
                    <div><span class="font-semibold text-[#E6EEF8]">Département :</span>
                        @if ($user->department)
                            <span class="ms-1 inline-flex items-center rounded-lg border border-[rgba(0,209,255,0.25)] bg-[#10192B] px-2 py-0.5 text-xs font-semibold text-[#00D1FF]">{{ $user->department->code }}</span>
                            <span class="text-[#E6EEF8]">{{ $user->department->name }}</span>
                        @else
                            —
                        @endif
                    </div>
                    <div><span class="font-semibold text-[#E6EEF8]">Rôle :</span>
                        <span class="text-[#E6EEF8]">{{ $user->institutionalRole?->name ?? '—' }}</span>
                    </div>
                </dl>
            </div>
        </div>
        <div class="dgcpt-surface p-6 shadow-sm sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="dgcpt-surface p-6 shadow-sm sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="dgcpt-surface p-6 shadow-sm sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
