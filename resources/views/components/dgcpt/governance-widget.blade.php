@props([
    'title' => null,
    'value' => null,
    'caption' => null,
])

<div {{ $attributes->class('rounded-2xl border border-[rgba(255,255,255,0.05)] bg-[rgba(255,255,255,0.03)] p-4') }}>
    @if ($title)
        <p class="text-xs uppercase tracking-wide text-[#73D8FF]">{{ $title }}</p>
    @endif
    <p class="mt-2 text-2xl font-bold text-[#E6EEF8]">{{ $value }}</p>
    @if ($caption)
        <p class="mt-1 text-xs text-[#9FB3C8]">{{ $caption }}</p>
    @endif
</div>
