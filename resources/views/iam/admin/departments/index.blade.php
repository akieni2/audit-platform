<x-app-layout>
    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Pôles / départements</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Structure organisationnelle DGCPT — codes, rattachements et supervision.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.home') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Tableau de bord admin</a>
                <a href="{{ route('admin.departments.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">Nouveau département</a>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-800 dark:text-green-200 border border-green-200 dark:border-green-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Code</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Nom</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Superviseur</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">Utilisateurs actifs</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">Statut</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($departments as $d)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $d->code }}</td>
                            <td class="px-4 py-3">{{ $d->name }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $d->supervisor?->displayName() ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ $d->users_count }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($d->active)
                                    <span class="inline-flex rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:text-emerald-200">Actif</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-700 px-2 py-0.5 text-xs font-medium text-slate-700 dark:text-slate-200">Inactif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.departments.edit', $d) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-semibold">Modifier</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $departments->links() }}
        </div>
    </div>
</x-app-layout>
