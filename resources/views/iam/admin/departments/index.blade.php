<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2 sm:px-0">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="dgcpt-card-title">Structure</p>
                <h1 class="dgcpt-page-title">Pôles / départements</h1>
                <p class="mt-1 text-sm dgcpt-text-muted">Codes, rattachements et supervision.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.home') }}" class="text-sm font-semibold text-[#00D1FF] hover:underline">Tableau de bord admin</a>
                <a href="{{ route('admin.departments.create') }}" class="inline-flex rounded-xl bg-gradient-to-r from-[#0A2A66] to-blue-950 px-4 py-2 text-sm font-bold uppercase tracking-wider text-white ring-1 ring-[rgba(0,209,255,0.3)]">Nouveau département</a>
            </div>
        </div>

        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="dgcpt-table-wrap shadow-sm">
            <table class="dgcpt-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Nom</th>
                        <th>Superviseur</th>
                        <th class="text-right">Utilisateurs actifs</th>
                        <th class="text-right">Statut</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($departments as $d)
                        <tr>
                            <td class="font-semibold text-[#00D1FF]">{{ $d->code }}</td>
                            <td class="text-[#E6EEF8]">{{ $d->name }}</td>
                            <td class="text-[#9FB3C8]">{{ $d->supervisor?->displayName() ?? '—' }}</td>
                            <td class="text-right font-semibold text-[#E6EEF8]">{{ $d->users_count }}</td>
                            <td class="text-right">
                                @if ($d->active)
                                    <span class="inline-flex rounded-lg border border-[rgba(0,168,107,0.35)] bg-[#10192B] px-2 py-0.5 text-xs font-semibold text-[#00A86B]">Actif</span>
                                @else
                                    <span class="inline-flex rounded-lg border border-[rgba(148,163,184,0.3)] bg-[#10192B] px-2 py-0.5 text-xs font-semibold text-[#9FB3C8]">Inactif</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.departments.edit', $d) }}" class="text-xs font-semibold text-[#00D1FF] hover:underline">Modifier</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-[#9FB3C8]">
            {{ $departments->links() }}
        </div>
    </div>
</x-app-layout>
