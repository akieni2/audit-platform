<div class="{{ $depth > 0 ? 'ml-4 border-l border-[rgba(0,209,255,0.15)] pl-3' : '' }}">
    <div class="flex flex-wrap items-center gap-2 py-1">
        <span class="font-mono text-xs text-[#73D8FF]">{{ $node['code'] }}</span>
        <span class="font-semibold text-[#E6EEF8]">{{ $node['name'] }}</span>
        <span class="rounded bg-[rgba(0,209,255,0.12)] px-2 py-0.5 text-xs text-[#9FB3C8]">{{ $node['entity_type_label'] }}</span>
        @if (! empty($node['province']))
            <span class="text-xs text-[#9FB3C8]">{{ $node['province'] }}</span>
        @endif
        @if ($node['entity_type'] === 'provincial')
            <a href="{{ route('dgcpt.consolidation.province', ['treasuryEntity' => $node['id']]) }}" class="text-xs text-[#00D1FF] hover:underline">Vue province</a>
        @endif
    </div>
    @if (! empty($node['services']))
        <ul class="mb-2 ml-2 text-xs text-[#9FB3C8]">
            @foreach ($node['services'] as $service)
                <li>{{ $service['code'] }} — {{ $service['name'] }}</li>
            @endforeach
        </ul>
    @endif
    @foreach ($node['children'] ?? [] as $child)
        @include('dgcpt.hierarchy.partials.node', ['node' => $child, 'depth' => $depth + 1])
    @endforeach
</div>
