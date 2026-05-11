@props(['title', 'time' => null, 'tone' => 'info', 'read' => false])

@php
    $border = [
        'critical' => 'border-l-red-500',
        'warning' => 'border-l-dgcpt-yellow',
        'info' => 'border-l-dgcpt-cyan',
        'success' => 'border-l-dgcpt-green',
    ][$tone] ?? 'border-l-dgcpt-cyan';
    $bg = $read
        ? 'bg-[#0B1220]'
        : 'bg-[#10192B]';
@endphp

<div {{ $attributes->merge(['class' => 'dgcpt-notification-card rounded-xl border border-[rgba(0,209,255,0.18)] shadow-[0_8px_28px_rgba(0,0,0,0.28)] '.$border.' border-l-4 pl-4 pr-3 py-3 '.$bg]) }}>
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="min-w-0">
            <p class="text-sm font-bold tracking-tight text-[#E6EEF8]">{{ $title }}</p>
            @if ($time)
                <p class="mt-0.5 text-xs font-medium uppercase tracking-wider text-[#9FB3C8]">{{ $time }}</p>
            @endif
            <div class="mt-2">{{ $slot }}</div>
        </div>
    </div>
</div>
