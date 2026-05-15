<div class="grid gap-4 md:grid-cols-4">
    @foreach ($swotView['kpis'] as $label => $value)
        <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
            <p class="text-xs uppercase tracking-wide text-[#73D8FF]">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $label)) }}</p>
            <p class="mt-2 text-2xl font-bold text-[#E6EEF8]">{{ $value }}</p>
        </div>
    @endforeach
</div>

<div class="mt-6 grid gap-4 md:grid-cols-2">
    @foreach (($swotView['summary']['quadrants'] ?? []) as $quadrant)
        <div class="rounded-[2rem] border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.22em] text-[#73D8FF]">{{ $quadrant['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold text-[#E6EEF8]">{{ $quadrant['score'] }}</p>
                </div>
                <span class="rounded-full bg-[rgba(0,168,107,0.12)] px-3 py-1 text-xs font-semibold text-[#7EF2BE]">{{ $quadrant['count'] }} items</span>
            </div>
        </div>
    @endforeach
</div>
