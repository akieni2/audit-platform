@props([
    'tabs' => [],
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }}>
    @foreach ($tabs as $tab)
        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ ($tab['active'] ?? false) ? 'bg-[rgba(0,168,107,0.12)] text-[#7EF2BE]' : 'bg-[rgba(0,209,255,0.08)] text-[#BFD2E6]' }}">
            {{ $tab['label'] ?? 'Tab' }}
        </span>
    @endforeach
</div>
