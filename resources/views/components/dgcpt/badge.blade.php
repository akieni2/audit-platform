@props([
    'label',
    'tone' => 'info',
])

@php
    $classes = match ($tone) {
        'success' => 'bg-[rgba(0,168,107,0.12)] text-[#7EF2BE]',
        'warning' => 'bg-[rgba(244,208,0,0.12)] text-[#FFD479]',
        'danger' => 'bg-[rgba(255,90,90,0.12)] text-[#FFB4B4]',
        default => 'bg-[rgba(0,209,255,0.08)] text-[#73D8FF]',
    };
@endphp

<span {{ $attributes->merge(['class' => 'rounded-full px-3 py-1 text-xs font-semibold '.$classes]) }}>
    {{ $label }}
</span>
