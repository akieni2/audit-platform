<div class="flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-[rgba(0,209,255,0.12)] bg-[rgba(5,8,22,0.72)] px-4 py-3">
    <div class="flex flex-wrap items-center gap-2">
        <span class="rounded-full bg-[rgba(0,209,255,0.10)] px-3 py-1 text-xs font-semibold text-[#73D8FF]">
            {{ $canvas['stats']['nodes'] }} noeuds
        </span>
        <span class="rounded-full bg-[rgba(0,168,107,0.10)] px-3 py-1 text-xs font-semibold text-[#7EF2BE]">
            {{ $canvas['stats']['transitions'] }} transitions
        </span>
        <span class="rounded-full bg-[rgba(255,212,121,0.10)] px-3 py-1 text-xs font-semibold text-[#FFD479]">
            {{ $canvas['stats']['approval_nodes'] }} étapes approval
        </span>
        @if (($canvas['stats']['invalid_transitions'] ?? 0) > 0)
            <span class="rounded-full bg-[rgba(255,90,90,0.10)] px-3 py-1 text-xs font-semibold text-[#FFB4B4]">
                {{ $canvas['stats']['invalid_transitions'] }} transitions à corriger
            </span>
        @endif
    </div>

    <div class="flex items-center gap-2">
        <button type="button" class="dgcpt-btn-outline !px-3 !py-2 text-xs" data-canvas-zoom="out">-</button>
        <span class="min-w-16 text-center text-xs font-semibold text-[#BFD2E6]" data-canvas-zoom-label>100%</span>
        <button type="button" class="dgcpt-btn-outline !px-3 !py-2 text-xs" data-canvas-zoom="in">+</button>
        <button type="button" class="dgcpt-btn-outline !px-3 !py-2 text-xs" data-canvas-zoom="reset">Réinitialiser</button>
    </div>
</div>
