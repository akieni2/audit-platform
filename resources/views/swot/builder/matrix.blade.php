<div class="grid gap-4 md:grid-cols-2">
    @foreach ($builder['matrix'] as $quadrant)
        <div class="rounded-[2rem] border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.22em] text-[#73D8FF]">{{ strtoupper($quadrant['key']) }}</p>
                    <h3 class="mt-2 text-xl font-bold text-[#E6EEF8]">{{ $quadrant['label'] }}</h3>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-[#E6EEF8]">{{ $quadrant['count'] }}</p>
                    <p class="text-xs text-[#9FB3C8]">score {{ $quadrant['score'] }}</p>
                </div>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($quadrant['entries'] as $entry)
                    <div class="rounded-2xl border border-[rgba(255,255,255,0.05)] bg-[rgba(255,255,255,0.02)] p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-[#E6EEF8]">{{ $entry->title }}</p>
                                <p class="mt-1 text-xs text-[#9FB3C8]">{{ $entry->description ?: 'Entree SWOT dynamique.' }}</p>
                            </div>
                            <span class="rounded-full bg-[rgba(0,168,107,0.12)] px-2.5 py-1 text-xs font-semibold text-[#7EF2BE]">
                                {{ $entry->priority_level?->label() ?? $entry->priority_level }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-[#9FB3C8]">Aucune entree dans ce quadrant.</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
