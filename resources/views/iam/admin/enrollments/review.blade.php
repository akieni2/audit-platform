<x-app-layout>
    <div class="py-10 max-w-3xl mx-auto px-4 space-y-8">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Approbation</p>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Demande : {{ $user->displayName() }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</p>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-2 text-sm">
            <p><span class="text-gray-500 dark:text-gray-400">Département demandé :</span>
                <strong>{{ $user->registrationRequestedDepartment?->code ?? '—' }}</strong>
                — {{ $user->registrationRequestedDepartment?->name ?? '—' }}</p>
            <p><span class="text-gray-500 dark:text-gray-400">Fonction :</span> {{ $user->fonction ?? $user->position ?? '—' }}</p>
            <p><span class="text-gray-500 dark:text-gray-400">Matricule :</span> {{ $user->matricule ?? '—' }}</p>
            <p><span class="text-gray-500 dark:text-gray-400">Téléphone :</span> {{ $user->telephone ?? '—' }}</p>
        </div>

        <form method="post" action="{{ route('admin.enrollments.approve', $user) }}" class="space-y-6 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
            @csrf
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Approuver et activer</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">Attribuez le rôle institutionnel et le département réel d'affectation.</p>

            <div>
                <x-input-label for="role_id" value="Rôle institutionnel" />
                <select id="role_id" name="role_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                    <option value="">— Choisir —</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->name }} ({{ $role->slug }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="department_id" value="Département / Pôle d'affectation" />
                <select id="department_id" name="department_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
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
                <a href="{{ route('admin.enrollments.index') }}" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 dark:border-gray-600 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-900">
                    Annuler
                </a>
            </div>
        </form>

        <form method="post" action="{{ route('admin.enrollments.reject', $user) }}" class="rounded-lg border border-red-200 dark:border-red-900 bg-red-50/50 dark:bg-red-950/20 p-6" onsubmit="return confirm('Rejeter définitivement cette demande ?');">
            @csrf
            <h2 class="text-lg font-semibold text-red-900 dark:text-red-100">Rejeter la demande</h2>
            <p class="mt-1 text-sm text-red-800 dark:text-red-200">Le demandeur ne pourra pas se connecter.</p>
            <x-primary-button type="submit" class="mt-4 !bg-red-700 hover:!bg-red-600">Rejeter</x-primary-button>
        </form>
    </div>
</x-app-layout>
