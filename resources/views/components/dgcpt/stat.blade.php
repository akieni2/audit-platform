@props([
    'label',
    'value',
    'accent' => '#73D8FF',
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4']) }}>
    <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">{{ $label }}</p>
    <p class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ $value }}</p>
    <div class="mt-3 h-1.5 rounded-full" style="background: color-mix(in srgb, {{ $accent }} 20%, transparent);">
        <div class="h-1.5 rounded-full" style="width: 100%; background: {{ $accent }};"></div>
    </div>
</div>
