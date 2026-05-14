<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Observability Center</p>
                <h1 class="dgcpt-page-title">Workflow observability center</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">Vue consolidée des `workflow_execution_logs`, `business_events`, `runtime_metrics` et contrôles d’intégrité.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('workflow-runtime.dashboard') }}" class="dgcpt-btn-outline">Dashboard runtime</a>
                <a href="{{ route('dashboard') }}" class="dgcpt-btn-outline">Retour dashboard</a>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="dgcpt-surface p-6 shadow-sm">
                <p class="dgcpt-card-title">Business events</p>
                <h2 class="text-xl font-bold text-[#E6EEF8]">Événements métier</h2>
                <div class="mt-5 space-y-3">
                    @forelse ($businessEvents as $event)
                        <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                            <p class="text-sm font-semibold text-[#E6EEF8]">{{ $event->event_name }}</p>
                            <div class="mt-2 flex flex-wrap gap-3 text-xs text-[#BFD2E6]">
                                <span>{{ $event->occurred_at?->format('d/m/Y H:i') }}</span>
                                @if ($event->actor)
                                    <span>{{ $event->actor->displayName() }}</span>
                                @endif
                                <span>Status: {{ $event->status }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-[#9FB3C8]">Aucun business event visible.</p>
                    @endforelse
                </div>
            </div>

            <div class="dgcpt-surface p-6 shadow-sm">
                <p class="dgcpt-card-title">Runtime metrics</p>
                <h2 class="text-xl font-bold text-[#E6EEF8]">Mesures runtime</h2>
                <div class="mt-5 space-y-3">
                    @forelse ($runtimeMetrics as $metric)
                        <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                            <p class="text-sm font-semibold text-[#E6EEF8]">{{ $metric->metric_key }}</p>
                            <div class="mt-2 flex flex-wrap gap-3 text-xs text-[#BFD2E6]">
                                <span>Valeur: {{ $metric->value }}</span>
                                <span>Type: {{ $metric->metric_type }}</span>
                                <span>{{ $metric->recorded_at?->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-[#9FB3C8]">Aucune métrique runtime visible.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="dgcpt-surface p-6 shadow-sm">
                <p class="dgcpt-card-title">Integrity checks</p>
                <h2 class="text-xl font-bold text-[#E6EEF8]">Projection integrity</h2>
                <div class="mt-5 space-y-3">
                    @forelse ($integrityChecks as $check)
                        <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                            <p class="text-sm font-semibold text-[#E6EEF8]">{{ $check->projection_type }}</p>
                            <div class="mt-2 flex flex-wrap gap-3 text-xs text-[#BFD2E6]">
                                <span>Status: {{ $check->status }}</span>
                                <span>Mismatches: {{ $check->mismatch_count }}</span>
                                <span>{{ $check->checked_at?->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-[#9FB3C8]">Aucun contrôle d’intégrité disponible.</p>
                    @endforelse
                </div>
            </div>

            <div class="dgcpt-surface p-6 shadow-sm">
                <p class="dgcpt-card-title">Templates workflow</p>
                <h2 class="text-xl font-bold text-[#E6EEF8]">Définitions actives</h2>
                <div class="mt-5 space-y-3">
                    @forelse ($workflowTemplates as $template)
                        <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                            <p class="text-sm font-semibold text-[#E6EEF8]">{{ $template->name }}</p>
                            <div class="mt-2 flex flex-wrap gap-3 text-xs text-[#BFD2E6]">
                                <span>Stages: {{ $template->stages_count }}</span>
                                <span>Instances: {{ $template->instances_count }}</span>
                                <span>Status: {{ $template->status?->value ?? $template->status }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-[#9FB3C8]">Aucun template workflow visible.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
