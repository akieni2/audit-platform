@php
    $from = $nodeMap[$edge['from_stage_id']] ?? null;
    $to = $nodeMap[$edge['to_stage_id']] ?? null;
@endphp

@if ($from && $to)
    @php
        $fromX = (int) $from['x'] + 288;
        $fromY = (int) $from['y'] + 68;
        $toX = (int) $to['x'];
        $toY = (int) $to['y'] + 68;
        $width = max(24, $toX - $fromX);
        $top = min($fromY, $toY);
        $height = max(2, abs($toY - $fromY));
        $lineColor = $edge['is_valid'] ? 'rgba(0,209,255,0.45)' : 'rgba(255,90,90,0.55)';
    @endphp

    <div class="pointer-events-none absolute" style="left: {{ $fromX }}px; top: {{ $top }}px; width: {{ $width }}px; height: {{ max(4, $height + 8) }}px;">
        <div class="absolute left-0 right-0 top-1/2 h-[2px] -translate-y-1/2 rounded-full" style="background: {{ $lineColor }};"></div>
        <div class="absolute right-0 top-1/2 h-3 w-3 -translate-y-1/2 rotate-45 border-r-2 border-t-2" style="border-color: {{ $lineColor }};"></div>
        @if (abs($toY - $fromY) > 8)
            <div class="absolute right-0 w-[2px] rounded-full" style="top: {{ $fromY <= $toY ? '50%' : '0' }}; bottom: {{ $fromY <= $toY ? '0' : '50%' }}; background: {{ $lineColor }};"></div>
        @endif
        <div class="absolute left-1/2 top-0 -translate-x-1/2 rounded-full border px-2 py-0.5 text-[10px] font-semibold backdrop-blur"
             style="border-color: {{ $lineColor }}; color: {{ $edge['is_valid'] ? '#73D8FF' : '#FFB4B4' }}; background: rgba(5, 8, 22, 0.78);">
            {{ $edge['is_automatic'] ? 'AUTO' : 'MANUAL' }}
        </div>
    </div>
@endif
