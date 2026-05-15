<div class="space-y-4">
    <div class="rounded-2xl border border-[rgba(0,209,255,0.12)] bg-[rgba(5,8,22,0.72)] p-5">
        <p class="text-sm font-semibold text-[#E6EEF8]">{{ $ui['title'] }}</p>
        <p class="mt-2 text-sm text-[#9FB3C8]">{{ $ui['description'] ?: 'Stage SWOT integre au runtime workflow.' }}</p>
        <div class="mt-4 flex flex-wrap gap-3 text-xs text-[#BFD2E6]">
            <span>Component key: {{ $ui['component_key'] }}</span>
            <span>Analyses: {{ $ui['metrics']['swot_analyses'] ?? 0 }}</span>
        </div>
    </div>
    <a href="{{ $ui['action_url'] }}" class="dgcpt-btn-primary">Ouvrir le stage SWOT</a>
</div>
