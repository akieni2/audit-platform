@props([
    'label' => null,
    'value' => 0,
    'max' => 100,
])

@php
    $percentage = $max > 0 ? min(100, max(0, round(($value / $max) * 100))) : 0;
@endphp

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    @if ($label)
        <div class="flex items-center justify-between gap-3 text-xs">
            <span class="font-semibold text-[#E6EEF8]">{{ $label }}</span>
            <span class="text-[#9FB3C8]">{{ $percentage }}%</span>
        </div>
    @endif
    <div class="h-2 rounded-full bg-[rgba(255,255,255,0.06)]">
        <div class="h-2 rounded-full bg-gradient-to-r from-[#00D1FF] to-[#00A86B]" style="width: {{ $percentage }}%;"></div>
    </div>
</div>
