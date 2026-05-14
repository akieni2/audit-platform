@php
    $width = max(1, (int) data_get($canvas, 'layout.width', 1440));
    $height = max(1, (int) data_get($canvas, 'layout.height', 760));
@endphp

<div class="rounded-3xl border border-[rgba(0,209,255,0.12)] bg-[rgba(5,8,22,0.72)] p-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#73D8FF]">Minimap</p>
            <p class="mt-1 text-xs text-[#9FB3C8]">Vue compacte des lanes et positions.</p>
        </div>
        <span class="rounded-full bg-[rgba(0,209,255,0.08)] px-2.5 py-1 text-[11px] font-semibold text-[#BFD2E6]">
            {{ count($canvas['layout']['lanes'] ?? []) }} lanes
        </span>
    </div>

    <div class="relative mt-4 overflow-hidden rounded-2xl border border-[rgba(0,209,255,0.08)] bg-[rgba(10,42,102,0.18)]" style="height: {{ data_get($canvas, 'layout.minimap.height', 140) }}px;">
        @foreach ($canvas['nodes'] as $node)
            <div
                class="absolute rounded-sm"
                title="{{ $node['name'] }}"
                style="
                    left: {{ round(($node['x'] / $width) * 100, 2) }}%;
                    top: {{ round(($node['y'] / $height) * 100, 2) }}%;
                    width: 5%;
                    height: 12%;
                    background: {{ $node['color'] ?: '#73D8FF' }};
                    opacity: {{ $node['is_selected'] ? '1' : '0.68' }};
                "
            ></div>
        @endforeach
    </div>

    <div class="mt-4 space-y-2">
        @foreach ($canvas['layout']['lanes'] ?? [] as $lane)
            <div class="flex items-center justify-between gap-3 rounded-2xl border border-[rgba(0,209,255,0.06)] px-3 py-2 text-xs text-[#BFD2E6]">
                <span>{{ $lane['label'] }}</span>
                <span class="font-semibold text-[#73D8FF]">{{ $lane['count'] }} étapes</span>
            </div>
        @endforeach
    </div>
</div>
