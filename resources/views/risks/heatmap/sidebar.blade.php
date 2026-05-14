<div class="space-y-4">
    <div class="dgcpt-surface p-5 shadow-sm">
        <p class="dgcpt-card-title">Clustering</p>
        <h2 class="text-lg font-bold text-[#E6EEF8]">Groupes de risques</h2>
        <div class="mt-4 space-y-3">
            @foreach ($heatmapView['clusters'] as $cluster)
                @include('risks.heatmap.risk-node', ['cluster' => $cluster])
            @endforeach
        </div>
    </div>
</div>
