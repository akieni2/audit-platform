<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-8 px-4 py-10">
        <div>
            <p class="dgcpt-card-title">Approbation</p>
            <h1 class="dgcpt-page-title">Demande : {{ $user->displayName() }}</h1>
            <p class="mt-1 text-sm dgcpt-text-muted">{{ $user->email }}</p>
        </div>

        <div class="dgcpt-surface space-y-2 p-6 text-sm shadow-sm">
            <p class="text-[#E6EEF8]">
                <span class="text-[#9FB3C8]">Département demandé :</span>
                <strong class="font-semibold text-[#E6EEF8]">{{ $user->registrationRequestedDepartment?->code ?? '—' }}</strong>
                — {{ $user->registrationRequestedDepartment?->name ?? '—' }}
            </p>
            <p><span class="text-[#9FB3C8]">Fonction :</span> <span class="text-[#E6EEF8]">{{ $user->fonction ?? $user->position ?? '—' }}</span></p>
            <p><span class="text-[#9FB3C8]">Matricule :</span> <span class="text-[#E6EEF8]">{{ $user->matricule ?? '—' }}</span></p>
            <p><span class="text-[#9FB3C8]">Téléphone :</span> <span class="text-[#E6EEF8]">{{ $user->telephone ?? '—' }}</span></p>
        </div>

        <form method="post" action="{{ route('admin.enrollments.approve', $user) }}" class="dgcpt-surface space-y-6 p-6 shadow-sm">
            @csrf
            <h2 class="text-base font-bold uppercase tracking-wider text-[#E6EEF8]">Approuver et activer</h2>
            <p class="text-sm text-[#9FB3C8]">Attribuez le rôle institutionnel et le département réel d'affectation.</p>

            <div>
                <x-input-label for="role_id" value="Rôle institutionnel" />
                <select id="role_id" name="role_id" required class="dgcpt-select shadow-sm">
                    <option value="">— Choisir —</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->name }} ({{ $role->slug }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="department_id" value="Département / Pôle d'affectation" />
                <select id="department_id" name="department_id" required class="dgcpt-select shadow-sm">
                    <option value="">— Choisir —</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}" @selected(old('department_id', $user->registration_requested_department_id) == $dept->id)>
                            {{ $dept->code }} — {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
            </div>

            <div class="flex flex-wrap gap-3">
                <x-primary-button type="submit">Approuver et activer</x-primary-button>
                <a href="{{ route('admin.enrollments.index') }}" class="dgcpt-btn-outline">Annuler</a>
            </div>
        </form>

        <form method="post" action="{{ route('admin.enrollments.reject', $user) }}" class="rounded-xl border border-[rgba(255,90,90,0.45)] bg-[#10192B] p-6 shadow-[0_8px_28px_rgba(0,0,0,0.28)]" onsubmit="return confirm('Rejeter définitivement cette demande ?');">
            @csrf
            <h2 class="text-base font-bold uppercase tracking-wider text-[#FF5A5A]">Rejeter la demande</h2>
            <p class="mt-1 text-sm text-[#9FB3C8]">Le demandeur ne pourra pas se connecter.</p>
            <x-primary-button type="submit" class="mt-4 !border-[rgba(255,90,90,0.5)] !bg-[#7f1d1d] hover:!bg-[#991b1b]">Rejeter</x-primary-button>
        </form>
    </div>
</x-app-layout>
