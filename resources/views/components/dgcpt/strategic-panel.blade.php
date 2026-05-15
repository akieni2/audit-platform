@props([
    'title' => null,
    'subtitle' => null,
])

<section {{ $attributes->class('rounded-[2rem] border border-[rgba(0,209,255,0.12)] bg-[linear-gradient(135deg,rgba(5,8,22,0.95),rgba(10,42,102,0.55))] p-6 shadow-sm') }}>
    @if ($title)
        <p class="dgcpt-card-title">{{ $title }}</p>
    @endif
    @if ($subtitle)
        <p class="mt-1 text-sm text-[#9FB3C8]">{{ $subtitle }}</p>
    @endif
    <div class="mt-4">
        {{ $slot }}
    </div>
</section>
