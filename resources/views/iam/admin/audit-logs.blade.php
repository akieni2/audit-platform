<x-app-layout>
    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Journal de sécurité</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Traçabilité des opérations sensibles (OWASP).</p>
            </div>
            <a href="{{ route('admin.home') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">← Administration</a>
        </div>

        <form method="get" action="{{ route('admin.security.audit-logs') }}" class="flex flex-wrap gap-3 items-end bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Module</label>
                <input type="text" name="module" value="{{ $filters['module'] ?? '' }}" placeholder="auth, iam…"
                       class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Action</label>
                <input type="text" name="action" value="{{ $filters['action'] ?? '' }}" placeholder="login_success…"
                       class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm" />
            </div>
            <button type="submit" class="rounded-md bg-slate-800 dark:bg-slate-600 px-4 py-2 text-sm font-semibold text-white">Filtrer</button>
            <a href="{{ route('admin.security.audit-logs') }}" class="text-sm text-indigo-600 dark:text-indigo-400 py-2">Réinitialiser</a>
        </form>

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Date</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Utilisateur</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Action</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Module</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">IP</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($logs as $log)
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-2">{{ $log->user?->email ?? '—' }}</td>
                            <td class="px-4 py-2 font-medium">{{ $log->action }}</td>
                            <td class="px-4 py-2">{{ $log->module }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $log->ip ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($log->description ?? '', 160) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>{{ $logs->links() }}</div>
    </div>
</x-app-layout>
