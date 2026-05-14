@php
    $nodeColor = $node['color'] ?: '#00D1FF';
@endphp

<div
    class="workflow-stage-card absolute w-72 rounded-[1.5rem] border bg-[rgba(7,17,31,0.92)] p-4 shadow-[0_24px_60px_rgba(5,8,22,0.45)] transition duration-200 hover:-translate-y-1"
    data-stage-id="{{ $node['id'] }}"
    data-stage-x="{{ $node['x'] }}"
    data-stage-y="{{ $node['y'] }}"
    draggable="true"
    style="left: {{ $node['x'] }}px; top: {{ $node['y'] }}px; border-color: {{ $nodeColor }};"
>
    <div class="flex items-start justify-between gap-3">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl text-xs font-black text-[#050816]" style="background: {{ $nodeColor }};">
                    {{ strtoupper(substr($node['code'] ?: $node['name'], 0, 2)) }}
                </span>
                <div>
                    <p class="text-sm font-semibold text-[#E6EEF8]">{{ $node['name'] }}</p>
                    <p class="text-[11px] font-mono uppercase tracking-[0.25em] text-[#7E92A7]">{{ $node['code'] }}</p>
                </div>
            </div>
            <p class="text-xs text-[#9FB3C8]">{{ $node['stage_type'] }} · {{ $node['execution_mode'] }}</p>
        </div>

        <div class="flex flex-col items-end gap-2">
            <span @class([
                'rounded-full px-2.5 py-1 text-[11px] font-semibold',
                'bg-[rgba(0,168,107,0.12)] text-[#7EF2BE]' => $node['is_selected'],
                'bg-[rgba(23,48,80,0.82)] text-[#73D8FF]' => ! $node['is_selected'],
            ])>
                {{ $node['is_selected'] ? 'Sélectionnée' : 'Designer' }}
            </span>
            <span class="rounded-full border border-[rgba(0,209,255,0.12)] px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#BFD2E6]">
                {{ $node['lane'] }}
            </span>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        @foreach ($node['badges'] as $badge)
            <span class="rounded-full bg-[rgba(0,209,255,0.08)] px-2.5 py-1 text-[11px] font-semibold text-[#BFD2E6]">{{ $badge }}</span>
        @endforeach
        <span class="rounded-full bg-[rgba(255,255,255,0.04)] px-2.5 py-1 text-[11px] font-semibold text-[#9FB3C8]">
            {{ $node['component_key'] }}
        </span>
    </div>

    <div class="mt-4 flex items-center justify-between gap-3 border-t border-[rgba(0,209,255,0.08)] pt-3 text-xs text-[#9FB3C8]">
        <span>XY <span data-position-label>{{ $node['x'] }}, {{ $node['y'] }}</span></span>
        <a href="{{ route('workflow-builder.edit', ['template' => $template, 'stage' => $node['id']]) }}"
           class="font-semibold text-[#73D8FF] hover:underline">
            Édition inline
        </a>
    </div>
</div>
