@props(['title', 'time' => null, 'tone' => 'info', 'read' => false])

@php
    $border = [
        'critical' => 'border-l-red-500',
        'warning' => 'border-l-dgcpt-yellow',
        'info' => 'border-l-dgcpt-cyan',
        'success' => 'border-l-dgcpt-green',
    ][$tone] ?? 'border-l-dgcpt-cyan';
    $bg = $read
        ? 'bg-slate-900/20 opacity-80'
        : 'bg-gradient-to-r from-dgcpt-blue/25 to-transparent';
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-white/5 '.$border.' border-l-4 pl-4 pr-3 py-3 '.$bg]) }}>
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="min-w-0">
            <p class="text-sm font-bold text-slate-50 tracking-tight">{{ $title }}</p>
            @if ($time)
                <p class="mt-0.5 text-xs text-slate-500">{{ $time }}</p>
            @endif
            <div class="mt-2">{{ $slot }}</div>
        </div>
    </div>
</div>
