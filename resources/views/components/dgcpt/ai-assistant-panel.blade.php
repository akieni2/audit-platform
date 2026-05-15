@props(['mission' => null])

<aside {{ $attributes->class('rounded-2xl border border-[rgba(0,209,255,0.2)] bg-[rgba(0,209,255,0.04)] p-4') }}>
    <div class="flex items-center gap-2">
        <span class="rounded-full bg-[#00D1FF]/20 px-2 py-0.5 text-xs font-semibold text-[#00D1FF]">IA assistive</span>
        <span class="text-xs text-[#9FB3C8]">Validation humaine requise</span>
    </div>
    <p class="mt-3 text-sm font-semibold text-[#E6EEF8]">Copilote audit & risques</p>
    @if ($mission)
        <p class="mt-1 text-xs text-[#9FB3C8]">Mission : {{ $mission->organisation }}</p>
        <div class="mt-4 flex flex-col gap-2" data-dgcpt-motion="stagger">
            <a href="{{ route('ai.assistant', $mission) }}" class="dgcpt-btn-outline text-center text-sm">Assistant mission</a>
            <a href="{{ route('ai.recommendations.mission', $mission) }}" class="dgcpt-btn-outline text-center text-sm">Recommandations</a>
        </div>
    @else
        <a href="{{ route('ai.index') }}" class="dgcpt-btn-outline mt-4 inline-block text-sm">Ouvrir le copilote</a>
    @endif
</aside>

