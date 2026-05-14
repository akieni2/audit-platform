<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Workflow Runtime</p>
                <h1 class="dgcpt-page-title">Dashboard live runtime</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">Monitoring global des workflows actifs, des blocages, des validations et des temps d’exécution.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('workflow-runtime.observability') }}" class="dgcpt-btn-outline">Observability center</a>
                <a href="{{ route('dashboard') }}" class="dgcpt-btn-outline">Retour dashboard</a>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <div class="dgcpt-surface p-4 shadow-sm"><p class="text-xs uppercase tracking-wide text-[#73D8FF]">Actifs</p><p class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ $runtimeDashboard['kpis']['active_workflows'] }}</p></div>
            <div class="dgcpt-surface p-4 shadow-sm"><p class="text-xs uppercase tracking-wide text-[#73D8FF]">Bloqués</p><p class="mt-2 text-3xl font-bold text-[#FFD479]">{{ $runtimeDashboard['kpis']['blocked_workflows'] }}</p></div>
            <div class="dgcpt-surface p-4 shadow-sm"><p class="text-xs uppercase tracking-wide text-[#73D8FF]">Approbations</p><p class="mt-2 text-3xl font-bold text-[#D8B4FE]">{{ $runtimeDashboard['kpis']['awaiting_approval'] }}</p></div>
            <div class="dgcpt-surface p-4 shadow-sm"><p class="text-xs uppercase tracking-wide text-[#73D8FF]">Completion</p><p class="mt-2 text-3xl font-bold text-[#7EF2BE]">{{ $runtimeDashboard['kpis']['completion_rate'] }}%</p></div>
            <div class="dgcpt-surface p-4 shadow-sm"><p class="text-xs uppercase tracking-wide text-[#73D8FF]">Temps moyen</p><p class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ $runtimeDashboard['kpis']['average_execution_minutes'] }} min</p></div>
            <div class="dgcpt-surface p-4 shadow-sm"><p class="text-xs uppercase tracking-wide text-[#73D8FF]">Risques</p><p class="mt-2 text-3xl font-bold text-[#FFB4B4]">{{ $runtimeDashboard['kpis']['detected_risks'] }}</p></div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.2fr,1.8fr]">
            <div class="dgcpt-surface p-6 shadow-sm">
                <p class="dgcpt-card-title">Heatmap runtime</p>
                <h2 class="text-xl font-bold text-[#E6EEF8]">Répartition santé workflow</h2>
                <div class="mt-5 grid gap-3">
                    <div class="rounded-2xl border border-[rgba(0,168,107,0.22)] bg-[rgba(6,28,22,0.72)] px-4 py-4 text-sm text-[#E6EEF8]">Healthy: {{ $runtimeDashboard['heatmap']['healthy'] }}</div>
                    <div class="rounded-2xl border border-[rgba(201,174,255,0.22)] bg-[rgba(28,15,39,0.72)] px-4 py-4 text-sm text-[#E6EEF8]">Attention: {{ $runtimeDashboard['heatmap']['attention'] }}</div>
                    <div class="rounded-2xl border border-[rgba(255,90,90,0.22)] bg-[rgba(36,10,15,0.72)] px-4 py-4 text-sm text-[#E6EEF8]">Critical: {{ $runtimeDashboard['heatmap']['critical'] }}</div>
                </div>
            </div>

            <div class="dgcpt-surface p-6 shadow-sm">
                <p class="dgcpt-card-title">Workflows actifs</p>
                <h2 class="text-xl font-bold text-[#E6EEF8]">Vue portefeuille</h2>
                <div class="mt-5 space-y-3">
                    @forelse ($runtimeDashboard['instances'] as $card)
                        @php
                            $instance = $card['instance'];
                            $summary = $card['summary'];
                        @endphp
                        <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-[#E6EEF8]">{{ $instance->mission?->organisation ?? 'Mission' }}</p>
                                    <p class="mt-1 text-xs text-[#9FB3C8]">{{ $instance->workflowTemplate?->name ?? 'Workflow' }}</p>
                                </div>
                                <a href="{{ route('workflow-runtime.show', $instance->mission_id) }}" class="text-sm font-semibold text-[#73D8FF] hover:underline">Ouvrir</a>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-3 text-xs text-[#BFD2E6]">
                                <span>Progression: {{ $summary['completion_percent'] }}%</span>
                                <span>Bloqués: {{ $summary['blocked_count'] }}</span>
                                <span>Approbations: {{ $summary['awaiting_approval_count'] }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-[#9FB3C8]">Aucun workflow actif pour votre périmètre.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
