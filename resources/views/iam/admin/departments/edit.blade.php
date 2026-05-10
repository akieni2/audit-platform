<x-app-layout>
    <div class="py-10 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Modifier {{ $department->code }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                <a href="{{ route('admin.departments.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">← Retour liste</a>
            </p>
        </div>

        @if (session('status'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-800 dark:text-green-200 border border-green-200 dark:border-green-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-md bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800">
                <ul class="list-disc ms-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('admin.departments.update', $department) }}" class="space-y-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 shadow-sm">
            @csrf
            @method('patch')
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Code <span class="text-red-600">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $department->code) }}" required maxlength="32"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                    <input type="text" name="type" value="{{ old('type', $department->type) }}" maxlength="64"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom <span class="text-red-600">*</span></label>
                <input type="text" name="name" value="{{ old('name', $department->name) }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                <textarea name="description" rows="3"
                          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm">{{ old('description', $department->description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Superviseur référent</label>
                <select name="supervisor_user_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm">
                    <option value="">—</option>
                    @foreach ($supervisors as $s)
                        <option value="{{ $s->id }}" @selected(old('supervisor_user_id', $department->supervisor_user_id) == $s->id)>{{ $s->displayName() }} ({{ $s->email }})</option>
                    @endforeach
                </select>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Couleur (UI)</label>
                    <input type="text" name="accent_color" value="{{ old('accent_color', $department->accent_color) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Logo (chemin ou URL)</label>
                    <input type="text" name="logo_path" value="{{ old('logo_path', $department->logo_path) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="hidden" name="active" value="0" />
                <input type="checkbox" name="active" value="1" @checked(old('active', $department->active)) />
                Département actif
            </label>
            <div class="flex gap-3">
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">Enregistrer</button>
                <a href="{{ route('admin.departments.index') }}" class="rounded-md border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Annuler</a>
            </div>
        </form>

        @if ($department->active)
            <div class="rounded-lg border border-red-200 dark:border-red-900 bg-red-50/50 dark:bg-red-900/10 p-4">
                <p class="text-sm font-medium text-red-900 dark:text-red-100 mb-2">Désactivation</p>
                <form method="post" action="{{ route('admin.departments.destroy', $department) }}" onsubmit="return confirm('Désactiver ce département ? Les rattachements existants restent en base.');">
                    @csrf
                    @method('delete')
                    <button type="submit" class="rounded-md border border-red-600 text-red-700 dark:text-red-300 px-4 py-2 text-sm font-semibold hover:bg-red-50 dark:hover:bg-red-900/30">
                        Désactiver le département
                    </button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
