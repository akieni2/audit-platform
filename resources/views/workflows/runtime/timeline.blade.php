<div class="dgcpt-surface p-6 shadow-sm">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="dgcpt-card-title">Timeline runtime</p>
            <h2 class="text-xl font-bold text-[#E6EEF8]">Journal chronologique</h2>
        </div>
    </div>

    <div class="mt-6 space-y-4">
        @forelse ($runtime->timeline as $entry)
            <div class="relative pl-6">
                <span class="absolute left-0 top-2 h-3 w-3 rounded-full bg-[#00D1FF]"></span>
                <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-[#E6EEF8]">{{ $entry->title }}</p>
                            @if ($entry->message)
                                <p class="mt-1 text-sm text-[#9FB3C8]">{{ $entry->message }}</p>
                            @endif
                        </div>
                        <span class="text-xs uppercase tracking-wide text-[#73D8FF]">{{ $entry->occurredAt->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-3 text-xs text-[#BFD2E6]">
                        <span>Source: {{ $entry->source }}</span>
                        <span>Statut: {{ $entry->status }}</span>
                        @if ($entry->stageName)
                            <span>Stage: {{ $entry->stageName }}</span>
                        @endif
                        @if ($entry->actorName)
                            <span>Acteur: {{ $entry->actorName }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-[#9FB3C8]">La timeline runtime est vide pour cette mission.</p>
        @endforelse
    </div>
</div>
