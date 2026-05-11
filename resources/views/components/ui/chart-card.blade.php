@props(['title', 'subtitle' => null, 'chartClass' => 'h-64'])

<div {{ $attributes->merge(['class' => 'dgcpt-panel']) }}>
    <h2 class="text-base font-bold uppercase tracking-wider text-[#E6EEF8]">{{ $title }}</h2>
    @if ($subtitle)
        <p class="mt-1 text-sm text-[#9FB3C8]">{{ $subtitle }}</p>
    @endif
    <div class="mt-4 {{ $chartClass }}" data-dgcpt-chart>
        {{ $slot }}
    </div>
</div>
