@props([
    'title' => null,
    'subtitle' => null,
    'tone' => 'info',
])

@php
    $tones = [
        'info' => 'border-[rgba(0,209,255,0.12)]',
        'success' => 'border-[rgba(0,168,107,0.25)]',
        'warning' => 'border-[rgba(245,158,11,0.25)]',
        'danger' => 'border-[rgba(255,90,90,0.25)]',
    ];
@endphp

<div {{ $attributes->class(['rounded-[2rem] border bg-[rgba(5,8,22,0.72)] p-5 shadow-sm', $tones[$tone] ?? $tones['info']]) }}>
    @if ($title)
        <p class="text-sm font-semibold text-[#E6EEF8]">{{ $title }}</p>
    @endif
    @if ($subtitle)
        <p class="mt-1 text-xs text-[#9FB3C8]">{{ $subtitle }}</p>
    @endif
    <div class="mt-4">
        {{ $slot }}
    </div>
</div>
