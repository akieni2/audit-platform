@props(['recommendation'])

<article class="rounded-xl border border-[rgba(255,255,255,0.06)] bg-[rgba(255,255,255,0.02)] p-4">
    <div class="flex flex-wrap items-center gap-2" data-dgcpt-motion="fade-up">
        <span class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">IA - {{ $recommendation->recommendation_type }}</span>
        <span class="rounded bg-[#F4D000]/15 px-2 py-0.5 text-xs text-[#F4D000]">{{ $recommendation->confidence_level }}</span>
        @if ($recommendation->requires_human_validation)
            <span class="text-xs text-[#9FB3C8]">Non contraignant</span>
        @endif
    </div>
    <h3 class="mt-2 font-semibold text-[#E6EEF8]">{{ $recommendation->title }}</h3>
    <p class="mt-2 text-sm text-[#9FB3C8]">{{ \Illuminate\Support\Str::limit($recommendation->summary, 320) }}</p>
</article>

