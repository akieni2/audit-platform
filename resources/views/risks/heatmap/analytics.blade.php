<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <div class="dgcpt-surface p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Critiques</p>
        <p class="mt-2 text-3xl font-bold text-[#FFB4B4]">{{ $heatmapView['analytics']['critical_count'] }}</p>
    </div>
    <div class="dgcpt-surface p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Densité</p>
        <p class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ $heatmapView['analytics']['density']['total_risks'] }}</p>
    </div>
    <div class="dgcpt-surface p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Vue mission</p>
        <p class="mt-2 text-lg font-bold text-[#7EF2BE]">{{ $heatmapView['mission']['label'] }}</p>
    </div>
    <div class="dgcpt-surface p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Départements actifs</p>
        <p class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ count($heatmapView['filters']['departments']) }}</p>
    </div>
</div>
