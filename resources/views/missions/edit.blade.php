<x-app-layout>
    <div class="max-w-2xl mx-auto px-4 py-10 space-y-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Modifier la mission</h1>

        <form method="POST" action="{{ route('missions.update', $mission) }}" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Organisation</label>
                <input type="text" name="organisation" value="{{ old('organisation', $mission->organisation) }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                <textarea name="description" rows="5" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">{{ old('description', $mission->description) }}</textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date début</label>
                    <input type="date" name="date_debut" value="{{ old('date_debut', $mission->date_debut) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date fin</label>
                    <input type="date" name="date_fin" value="{{ old('date_fin', $mission->date_fin) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900" />
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">
                    Enregistrer
                </button>
                <a href="{{ route('missions.show', $mission) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 dark:border-gray-600 dark:text-gray-200">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
