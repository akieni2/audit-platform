@props([
    'title' => null,
    'subtitle' => null,
])

<section {{ $attributes->merge(['class' => 'rounded-[2rem] border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-5']) }}>
    @if ($subtitle)
        <p class="dgcpt-card-title">{{ $subtitle }}</p>
    @endif
    @if ($title)
        <h3 class="text-lg font-bold text-[#E6EEF8]">{{ $title }}</h3>
    @endif
    <div class="{{ $title || $subtitle ? 'mt-4' : '' }}">
        {{ $slot }}
    </div>
</section>
