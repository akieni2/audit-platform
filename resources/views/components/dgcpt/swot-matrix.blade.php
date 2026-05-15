@props([
    'quadrants' => [],
])

<div {{ $attributes->class('grid gap-4 md:grid-cols-2') }}>
    @foreach ($quadrants as $quadrant)
        <x-dgcpt.swot-card :title="$quadrant['label'] ?? 'Quadrant'" :subtitle="'Score '.($quadrant['score'] ?? 0)">
            <div class="space-y-2">
                @foreach (($quadrant['entries'] ?? []) as $entry)
                    <div class="rounded-2xl bg-[rgba(255,255,255,0.03)] px-4 py-3 text-sm text-[#BFD2E6]">
                        {{ is_object($entry) ? $entry->title : data_get($entry, 'title') }}
                    </div>
                @endforeach
            </div>
        </x-dgcpt.swot-card>
    @endforeach
</div>
