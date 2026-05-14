@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'dgcpt-surface p-6 shadow-sm']) }}>
    @if ($title || $subtitle)
        <div class="mb-4">
            @if ($subtitle)
                <p class="dgcpt-card-title">{{ $subtitle }}</p>
            @endif
            @if ($title)
                <h3 class="text-lg font-bold text-[#E6EEF8]">{{ $title }}</h3>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
