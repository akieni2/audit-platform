<x-app-layout>
    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Demandes d'enrôlement</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Validation Super Admin — comptes issus de l'inscription publique (aucun accès avant approbation).
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.enrollments.index', ['status' => 'pending']) }}"
                   class="rounded-md px-3 py-2 text-sm font-semibold {{ ($status ?? 'pending') === 'pending' ? 'bg-indigo-600 text-white' : 'border border-gray-300 text-gray-700 dark:border-gray-600 dark:text-gray-200' }}">
                    En attente
                </a>
                <a href="{{ route('admin.enrollments.index', ['status' => 'rejected']) }}"
                   class="rounded-md px-3 py-2 text-sm font-semibold {{ ($status ?? '') === 'rejected' ? 'bg-indigo-600 text-white' : 'border border-gray-300 text-gray-700 dark:border-gray-600 dark:text-gray-200' }}">
                    Rejetées
                </a>
                <a href="{{ route('admin.enrollments.index', ['status' => 'all']) }}"
                   class="rounded-md px-3 py-2 text-sm font-semibold {{ ($status ?? '') === 'all' ? 'bg-indigo-600 text-white' : 'border border-gray-300 text-gray-700 dark:border-gray-600 dark:text-gray-200' }}">
                    Toutes
                </a>
                <a href="{{ route('admin.home') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline self-center">
                    Console admin
                </a>
            </div>
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

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Nom</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Prénom</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Email</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Téléphone</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Département demandé</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Fonction</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Matricule</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Date</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Statut</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($users as $u)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $u->name }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $u->prenom ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $u->email }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $u->telephone ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $u->registrationRequestedDepartment?->code ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $u->fonction ?? $u->position ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $u->matricule ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $u->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                                    {{ $u->approval_status === 'pending' ? 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-100' : '' }}
                                    {{ $u->approval_status === 'rejected' ? 'bg-red-100 text-red-900 dark:bg-red-900/40 dark:text-red-100' : '' }}
                                    {{ $u->approval_status === 'approved' ? 'bg-emerald-100 text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-100' : '' }}">
                                    {{ $u->approval_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($u->isPendingApproval())
                                    <a href="{{ route('admin.enrollments.review', $u) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Traiter</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Aucune demande dans cette liste.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div>{{ $users->links() }}</div>
        @endif
    </div>
</x-app-layout>
