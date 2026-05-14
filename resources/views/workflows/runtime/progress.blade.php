<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
    <div class="dgcpt-surface p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Progression</p>
        <p class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ $runtime->progress['completion_percent'] }}%</p>
    </div>
    <div class="dgcpt-surface p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Complétées</p>
        <p class="mt-2 text-3xl font-bold text-[#7EF2BE]">{{ $runtime->progress['completed_count'] }}</p>
    </div>
    <div class="dgcpt-surface p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Bloquées</p>
        <p class="mt-2 text-3xl font-bold text-[#FFD479]">{{ $runtime->progress['blocked_count'] }}</p>
    </div>
    <div class="dgcpt-surface p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Approbations</p>
        <p class="mt-2 text-3xl font-bold text-[#D8B4FE]">{{ $runtime->progress['awaiting_approval_count'] }}</p>
    </div>
    <div class="dgcpt-surface p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Durée moyenne</p>
        <p class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ $runtime->progress['average_duration_minutes'] }} min</p>
    </div>
</div>
