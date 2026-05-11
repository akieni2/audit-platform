@props(['tone' => 'neutral'])

@php
    $map = [
        'neutral' => 'bg-slate-500/15 text-slate-200 border-slate-500/30',
        'success' => 'bg-dgcpt-green/15 text-emerald-100 border-dgcpt-green/40',
        'warning' => 'bg-dgcpt-yellow/15 text-yellow-100 border-dgcpt-yellow/35',
        'danger' => 'bg-red-500/15 text-red-100 border-red-500/35',
        'info' => 'bg-dgcpt-cyan/15 text-cyan-100 border-dgcpt-cyan/35',
    ];
    $tone = $map[$tone] ?? $map['neutral'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-lg border px-2.5 py-0.5 text-[0.65rem] font-bold uppercase tracking-widest '.$tone]) }}>
    {{ $slot }}
</span>
