@props(['title' => null])

<div {{ $attributes->merge(['class' => 'dgcpt-panel']) }}>
    @if ($title)
        <h2 class="text-base font-bold uppercase tracking-widest text-slate-800 dark:text-slate-100 mb-1">{{ $title }}</h2>
    @endif
    {{ $slot }}
</div>
