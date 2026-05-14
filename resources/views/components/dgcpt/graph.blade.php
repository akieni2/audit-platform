@props([
    'title' => 'Graph',
    'value' => null,
    'caption' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-[2rem] border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-5']) }}>
    <div class="flex items-center justify-between gap-3">
        <h3 class="text-lg font-bold text-[#E6EEF8]">{{ $title }}</h3>
        @if ($value !== null)
            <span class="text-sm font-semibold text-[#73D8FF]">{{ $value }}</span>
        @endif
    </div>
    @if ($caption)
        <p class="mt-2 text-sm text-[#9FB3C8]">{{ $caption }}</p>
    @endif
    <div class="mt-4">
        {{ $slot }}
    </div>
</div>
