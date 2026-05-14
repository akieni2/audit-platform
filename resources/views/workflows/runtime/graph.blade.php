<div class="dgcpt-surface p-6 shadow-sm">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="dgcpt-card-title">Workflow Graph</p>
            <h2 class="text-xl font-bold text-[#E6EEF8]">Visualisation BPMN-lite</h2>
        </div>
    </div>

    <div class="mt-5 overflow-auto rounded-3xl border border-[rgba(0,209,255,0.12)] bg-[radial-gradient(circle_at_top,_rgba(10,42,102,0.45),_rgba(5,8,22,0.94))] p-6">
        <div class="relative min-h-[24rem] min-w-[52rem]">
            @foreach ($runtime->graph['edges'] as $edge)
                @php
                    $from = collect($runtime->graph['nodes'])->firstWhere('id', $edge['from']);
                    $to = collect($runtime->graph['nodes'])->firstWhere('id', $edge['to']);
                    $left = min(($from['x'] ?? 0), ($to['x'] ?? 0)) + 140;
                    $top = min(($from['y'] ?? 0), ($to['y'] ?? 0)) + 45;
                    $width = max(40, abs(($to['x'] ?? 0) - ($from['x'] ?? 0)));
                @endphp
                <div class="absolute h-[2px] {{ $edge['active_path'] ? 'bg-[#00D1FF]' : 'bg-[rgba(191,210,230,0.25)]' }}"
                     style="left: {{ $left }}px; top: {{ $top }}px; width: {{ $width }}px;"></div>
            @endforeach

            @foreach ($runtime->graph['nodes'] as $node)
                <div class="absolute w-56 rounded-2xl border px-4 py-3 shadow-lg"
                     style="left: {{ $node['x'] }}px; top: {{ $node['y'] }}px; border-color: {{ $node['color'] }}; background: rgba(7,17,31,0.92);">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-[#E6EEF8]">{{ $node['label'] }}</p>
                            <p class="mt-1 text-[11px] font-mono uppercase tracking-wide text-[#9FB3C8]">{{ $node['code'] }}</p>
                        </div>
                        @if ($node['is_current'])
                            <span class="rounded-full bg-[rgba(0,209,255,0.12)] px-2 py-1 text-[11px] font-semibold text-[#73D8FF]">Current</span>
                        @endif
                    </div>
                    <p class="mt-3 text-xs text-[#BFD2E6]">{{ $node['state_label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <script type="application/json" id="workflow-graph-json">
        @json($runtime->graph)
    </script>
</div>
