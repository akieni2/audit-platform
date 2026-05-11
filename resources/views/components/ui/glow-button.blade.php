@props(['href' => null, 'variant' => 'primary'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold uppercase tracking-widest transition focus:outline-none focus:ring-2 focus:ring-dgcpt-cyan focus:ring-offset-2 focus:ring-offset-dgcpt-ink';
    $variants = [
        'primary' => 'bg-gradient-to-r from-dgcpt-blue to-blue-900 text-white shadow-lg shadow-cyan-500/20 border border-cyan-500/30 hover:shadow-cyan-500/35',
        'outline' => 'border border-dgcpt-cyan/50 text-dgcpt-cyan bg-transparent hover:bg-dgcpt-cyan/10',
    ];
    $class = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $class]) }}>
        {{ $slot }}
    </button>
@endif
