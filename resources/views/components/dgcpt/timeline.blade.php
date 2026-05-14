@props([
    'items' => [],
])

<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @foreach ($items as $item)
        <div class="relative pl-6">
            <span class="absolute left-0 top-2 h-3 w-3 rounded-full bg-[#00D1FF]"></span>
            <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                <p class="text-sm font-semibold text-[#E6EEF8]">{{ $item['title'] ?? 'Événement' }}</p>
                @if (! empty($item['message']))
                    <p class="mt-1 text-sm text-[#9FB3C8]">{{ $item['message'] }}</p>
                @endif
            </div>
        </div>
    @endforeach
</div>
