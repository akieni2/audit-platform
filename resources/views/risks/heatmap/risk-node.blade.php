<div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-[#E6EEF8]">{{ $cluster['label'] }}</p>
            <p class="mt-1 text-xs text-[#9FB3C8]">Moyenne score {{ $cluster['average_score'] }} · critiques {{ $cluster['critical'] }}</p>
        </div>
        <span class="rounded-full bg-[rgba(0,209,255,0.08)] px-3 py-1 text-xs font-semibold text-[#73D8FF]">{{ $cluster['count'] }}</span>
    </div>

    <div class="mt-3 space-y-2">
        @foreach ($cluster['items'] as $item)
            <div class="rounded-xl border border-[rgba(255,255,255,0.05)] px-3 py-2 text-xs text-[#BFD2E6]">
                <p class="font-semibold text-[#E6EEF8]">{{ \Illuminate\Support\Str::limit($item['description'], 70) }}</p>
                <p class="mt-1 text-[#9FB3C8]">Score {{ $item['score'] }}</p>
            </div>
        @endforeach
    </div>
</div>
