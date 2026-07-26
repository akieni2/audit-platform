<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 py-2">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Administration numérique</p>
                <h1 class="dgcpt-page-title">Gestion électronique du courrier</h1>
                <p class="mt-1 text-sm dgcpt-text-muted">Registre officiel, affectations, délais et traçabilité des mouvements.</p>
            </div>
            <a href="{{ route('correspondence.create') }}" class="dgcpt-btn-primary">Enregistrer un courrier</a>
        </div>

        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 p-4 text-sm text-[#E6EEF8]">{{ session('status') }}</div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Aujourd’hui', $stats['today'], '#00D1FF'],
                ['À traiter', $stats['pending'], '#73D8FF'],
                ['Urgents', $stats['urgent'], '#F4D000'],
                ['En retard', $stats['overdue'], '#FF5A5A'],
            ] as [$label, $value, $color])
                <div class="dgcpt-surface p-5">
                    <p class="dgcpt-card-title">{{ $label }}</p>
                    <p class="mt-2 text-3xl font-black" style="color:{{ $color }}">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <form method="get" class="dgcpt-filter-bar">
            <div class="min-w-[14rem] flex-1">
                <label class="dgcpt-label" for="gec-q">Recherche</label>
                <input id="gec-q" class="dgcpt-input" type="search" name="q" value="{{ request('q') }}" placeholder="Référence, objet ou expéditeur">
            </div>
            <div class="w-full sm:w-56">
                <label class="dgcpt-label" for="gec-status">Statut</label>
                <select id="gec-status" class="dgcpt-select" name="status">
                    <option value="">Tous</option>
                    @foreach (\App\Models\CorrespondenceRecord::statusLabels() as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="dgcpt-btn-primary" type="submit">Filtrer</button>
        </form>

        <div class="dgcpt-table-wrap">
            <table class="dgcpt-table">
                <thead><tr><th>Référence</th><th>Objet</th><th>Urgence</th><th>Statut</th><th>Affectation</th><th>Échéance</th></tr></thead>
                <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td><a class="font-mono font-semibold text-[#00D1FF]" href="{{ route('correspondence.show', $record) }}">{{ $record->reference }}</a></td>
                        <td><strong>{{ $record->subject }}</strong><span class="block text-xs text-[#9FB3C8]">{{ $record->sender }}</span></td>
                        <td>{{ \App\Models\CorrespondenceRecord::urgencyLabels()[$record->urgency] ?? $record->urgency }}</td>
                        <td>{{ \App\Models\CorrespondenceRecord::statusLabels()[$record->status] ?? $record->status }}</td>
                        <td>{{ $record->assignee?->displayName() ?? $record->department?->name ?? 'Bureau du courrier' }}</td>
                        <td class="{{ $record->isOverdue() ? 'font-bold text-[#FF5A5A]' : 'text-[#9FB3C8]' }}">{{ $record->deadline_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-[#9FB3C8]">Aucun courrier dans votre périmètre.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $records->links() }}
    </div>
</x-app-layout>
