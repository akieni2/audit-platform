@props([
    'label' => null,
    'value' => null,
    'caption' => null,
])

<div {{ $attributes->class('rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4') }}>
    @if ($label)
        <p class="text-xs uppercase tracking-wide text-[#73D8FF]">{{ $label }}</p>
    @endif
    <p class="mt-2 text-lg font-bold text-[#E6EEF8]">{{ $value }}</p>
    @if ($caption)
        <p class="mt-1 text-xs text-[#9FB3C8]">{{ $caption }}</p>
    @endif
</div>
