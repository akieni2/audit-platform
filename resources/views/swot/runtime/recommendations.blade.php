<div class="space-y-4">
    @forelse ($swotView['recommendations'] as $recommendation)
        <div class="rounded-[2rem] border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-[#E6EEF8]">{{ $recommendation->title }}</p>
                    <p class="mt-2 text-sm text-[#9FB3C8]">{{ $recommendation->description }}</p>
                </div>
                <span class="rounded-full bg-[rgba(244,208,0,0.12)] px-3 py-1 text-xs font-semibold text-[#FFD479]">
                    {{ $recommendation->priority_level?->label() ?? $recommendation->priority_level }}
                </span>
            </div>
            <div class="mt-3 flex flex-wrap gap-3 text-xs text-[#BFD2E6]">
                <span>Role: {{ $recommendation->owner_role ?: 'A definir' }}</span>
                <span>Statut: {{ $recommendation->status }}</span>
                <span>Indice: {{ $recommendation->priority_index }}</span>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-[rgba(0,209,255,0.12)] p-5 text-sm text-[#9FB3C8]">
            Aucune recommandation SWOT generee.
        </div>
    @endforelse
</div>
