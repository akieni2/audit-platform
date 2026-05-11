@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'dgcpt-panel']) }}>
    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-50 tracking-tight">{{ $title }}</h2>
    @if ($subtitle)
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $subtitle }}</p>
    @endif
    <div class="mt-4 h-64" data-dgcpt-chart>
        {{ $slot }}
    </div>
</div>
