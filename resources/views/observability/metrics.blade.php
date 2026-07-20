<x-dgcpt.card title="Metrics & événements" subtitle="Activité système">
    <div class="grid gap-6 xl:grid-cols-2">
        <div class="space-y-3">
            @forelse ($runtimeMetrics as $metric)
                <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                    <p class="text-sm font-semibold text-[#E6EEF8]">{{ $metric->metric_key }}</p>
                    <p class="mt-1 text-xs text-[#9FB3C8]">Valeur {{ $metric->value }} · {{ $metric->recorded_at?->format('d/m/Y H:i') }}</p>
                </div>
            @empty
                <p class="text-sm text-[#9FB3C8]">Aucune mesure d’exécution visible.</p>
            @endforelse
        </div>

        <div class="space-y-3">
            @forelse ($businessEvents as $event)
                <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                    <p class="text-sm font-semibold text-[#E6EEF8]">{{ $event->event_name }}</p>
                    <p class="mt-1 text-xs text-[#9FB3C8]">{{ $event->occurred_at?->format('d/m/Y H:i') }} · {{ \App\Support\UiLabel::translate($event->status) }}</p>
                </div>
            @empty
                <p class="text-sm text-[#9FB3C8]">Aucun business event visible.</p>
            @endforelse
        </div>
    </div>
</x-dgcpt.card>
