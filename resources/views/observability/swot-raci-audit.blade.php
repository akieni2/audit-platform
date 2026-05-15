<div class="grid gap-6 xl:grid-cols-2">
    <div class="dgcpt-surface p-6 shadow-sm">
        <p class="dgcpt-card-title">SWOT audit trail</p>
        <div class="mt-4 space-y-3">
            @forelse ($swotAuditLogs as $item)
                <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                    <p class="text-sm font-semibold text-[#E6EEF8]">{{ $item->event_name }}</p>
                    <p class="mt-1 text-xs text-[#9FB3C8]">{{ $item->status ?: 'n/a' }} · {{ $item->occurred_at?->format('d/m/Y H:i') }}</p>
                </div>
            @empty
                <p class="text-sm text-[#9FB3C8]">Aucun evenement SWOT audite.</p>
            @endforelse
        </div>
    </div>

    <div class="dgcpt-surface p-6 shadow-sm">
        <p class="dgcpt-card-title">RACI audit trail</p>
        <div class="mt-4 space-y-3">
            @forelse ($raciAuditLogs as $item)
                <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                    <p class="text-sm font-semibold text-[#E6EEF8]">{{ $item->event_name }}</p>
                    <p class="mt-1 text-xs text-[#9FB3C8]">{{ $item->status ?: 'n/a' }} · {{ $item->occurred_at?->format('d/m/Y H:i') }}</p>
                </div>
            @empty
                <p class="text-sm text-[#9FB3C8]">Aucun evenement RACI audite.</p>
            @endforelse
        </div>
    </div>
</div>
