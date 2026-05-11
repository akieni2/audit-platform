<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-10 px-4 py-10">
        <header class="space-y-2">
            <p class="dgcpt-card-title">Inspection des services</p>
            <h1 class="dgcpt-page-title">Consolidation et validation</h1>
            <p class="text-sm dgcpt-text-muted">
                Supervision nationale : missions à contrôler, files de validation IS / COPRI, indicateurs critiques.
            </p>
        </header>

        <div class="dgcpt-kpi-grid">
            @foreach ($kpis as $label => $value)
                <div class="dgcpt-kpi-card">
                    <div class="dgcpt-kpi-label">{{ str_replace('_', ' ', $label) }}</div>
                    <div class="dgcpt-kpi-value">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <section class="space-y-3">
            <h2 class="dgcpt-section-title">Validations IS en attente</h2>
            <p class="text-sm dgcpt-text-muted">Missions au statut « clôturée », à examiner avant validation Inspection.</p>
            <div class="dgcpt-table-wrap shadow-sm">
                <table class="dgcpt-table">
                    <thead>
                        <tr>
                            <th>Mission</th>
                            <th>Pôle</th>
                            <th>Mis à jour</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($awaitingIs as $m)
                            <tr>
                                <td>{{ $m->organisation }}</td>
                                <td>{{ $m->department?->code ?? '—' }}</td>
                                <td>{{ $m->updated_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('missions.show', $m) }}" class="dgcpt-link">Traiter</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-[#9FB3C8]">Aucune mission en file IS.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="dgcpt-section-title">Validation COPRI en attente</h2>
            <p class="text-sm dgcpt-text-muted">Missions au statut « validée_IS ».</p>
            <div class="dgcpt-table-wrap shadow-sm">
                <table class="dgcpt-table">
                    <thead>
                        <tr>
                            <th>Mission</th>
                            <th>Pôle</th>
                            <th>Mis à jour</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($awaitingCopri as $m)
                            <tr>
                                <td>{{ $m->organisation }}</td>
                                <td>{{ $m->department?->code ?? '—' }}</td>
                                <td>{{ $m->updated_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('missions.show', $m) }}" class="dgcpt-link">Traiter</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-[#9FB3C8]">Aucune mission en file COPRI.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <p class="text-sm text-[#9FB3C8]">
            <a href="{{ route('dashboard') }}" class="dgcpt-link text-sm">← Tableau de bord départemental</a>
        </p>
    </div>
</x-app-layout>
