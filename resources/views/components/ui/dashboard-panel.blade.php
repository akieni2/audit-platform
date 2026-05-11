@props(['title' => null])

<div {{ $attributes->merge(['class' => 'dgcpt-panel']) }}>
    @if ($title)
        <h2 class="mb-2 text-base font-bold uppercase tracking-wider text-[#E6EEF8]">{{ $title }}</h2>
    @endif
    {{ $slot }}
</div>
