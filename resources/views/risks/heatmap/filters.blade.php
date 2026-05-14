<div class="dgcpt-surface p-5 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="dgcpt-card-title">Filtres dynamiques</p>
            <h2 class="text-lg font-bold text-[#E6EEF8]">Vue heatmap enterprise</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach ($heatmapView['filters']['scopes'] as $scope)
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $scope === $heatmapView['filters']['active_scope'] ? 'bg-[rgba(0,168,107,0.12)] text-[#7EF2BE]' : 'bg-[rgba(0,209,255,0.08)] text-[#BFD2E6]' }}">
                    {{ $scope }}
                </span>
            @endforeach
        </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        @foreach ($heatmapView['filters']['departments'] as $department)
            <span class="rounded-full border border-[rgba(0,209,255,0.10)] px-3 py-1 text-xs text-[#9FB3C8]">{{ $department }}</span>
        @endforeach
    </div>
</div>
