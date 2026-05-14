@props([
    'title' => 'Modal',
    'open' => false,
])

<div {{ $attributes->merge(['class' => $open ? 'block' : 'hidden']) }}>
    <div class="fixed inset-0 z-40 bg-[rgba(5,8,22,0.72)]"></div>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-6">
        <div class="w-full max-w-2xl rounded-[2rem] border border-[rgba(0,209,255,0.14)] bg-[#091322] p-6 shadow-[0_24px_60px_rgba(5,8,22,0.45)]">
            <h3 class="text-xl font-bold text-[#E6EEF8]">{{ $title }}</h3>
            <div class="mt-4">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
