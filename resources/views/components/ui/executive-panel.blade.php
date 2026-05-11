@props(['eyebrow' => null, 'title'])

<div {{ $attributes->merge(['class' => 'dgcpt-panel border-dgcpt-cyan/20']) }}>
    @if ($eyebrow)
        <p class="text-[0.65rem] font-bold uppercase tracking-[0.2em] text-dgcpt-cyan/90 mb-1">{{ $eyebrow }}</p>
    @endif
    <h2 class="text-xl font-extrabold uppercase tracking-wide text-slate-900 dark:text-white">{{ $title }}</h2>
    <div class="mt-4 text-slate-600 dark:text-slate-300 text-sm leading-relaxed">
        {{ $slot }}
    </div>
</div>
