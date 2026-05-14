<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
    <div class="dgcpt-surface p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Progression live</p>
        <p class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ $runtime->progress['completion_percent'] }}%</p>
        <div class="mt-3 h-2 rounded-full bg-[rgba(255,255,255,0.06)]">
            <div class="h-2 rounded-full bg-gradient-to-r from-[#00D1FF] to-[#00A86B]" style="width: {{ $runtime->progress['completion_percent'] }}%;"></div>
        </div>
    </div>
    <div class="dgcpt-surface p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Complétées</p>
        <p class="mt-2 text-3xl font-bold text-[#7EF2BE]">{{ $runtime->progress['completed_count'] }}</p>
        <p class="mt-1 text-xs text-[#9FB3C8]">Navigation précédente disponible: {{ $runtime->progress['navigation']['has_previous'] ? 'Oui' : 'Non' }}</p>
    </div>
    <div class="dgcpt-surface p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Bloquées</p>
        <p class="mt-2 text-3xl font-bold text-[#FFD479]">{{ $runtime->progress['blocked_count'] }}</p>
        <p class="mt-1 text-xs text-[#9FB3C8]">État global: {{ $runtime->progress['global_state'] }}</p>
    </div>
    <div class="dgcpt-surface p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Approbations</p>
        <p class="mt-2 text-3xl font-bold text-[#D8B4FE]">{{ $runtime->progress['awaiting_approval_count'] }}</p>
        <p class="mt-1 text-xs text-[#9FB3C8]">Next stage prête: {{ $runtime->progress['navigation']['has_next'] ? 'Oui' : 'Non' }}</p>
    </div>
    <div class="dgcpt-surface p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Durée moyenne</p>
        <p class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ $runtime->progress['average_duration_minutes'] }} min</p>
        <p class="mt-1 text-xs text-[#9FB3C8]">Instance #{{ $runtime->instance->id }}</p>
    </div>
</div>
