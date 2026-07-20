<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <header class="space-y-2">
            <p class="dgcpt-card-title">Questions et livrables</p>
            <h1 class="dgcpt-page-title">Bibliothèques de contrôle DGCPT</h1>
            <p class="text-sm text-[#9FB3C8]">Mesures de contrôle, questions types et livrables rattachés aux référentiels homologués.</p>
        </header>

        <div class="grid gap-6 xl:grid-cols-2">
            @foreach ($controlLibraries as $library)
                @php($questions = data_get($library->metadata, 'question_bank', []))
                @php($deliverables = data_get($library->metadata, 'deliverables', []))
                <div class="dgcpt-surface p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-lg font-bold text-[#E6EEF8]">{{ $library->name }}</p>
                            <p class="mt-1 text-xs uppercase tracking-wide text-[#73D8FF]">{{ $library->methodologyTemplate?->framework_key ?? 'custom' }}</p>
                        </div>
                        <span class="rounded-full bg-[rgba(0,209,255,0.12)] px-3 py-1 text-xs font-semibold text-[#73D8FF]">{{ $library->visibility_scope ?? 'shared' }}</span>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-4">
                        <x-ui.kpi-card label="Mesures" :value="$library->measures_count" accent="green" />
                        <x-ui.kpi-card label="Mappings" :value="$library->mappings_count" accent="violet" />
                        <x-ui.kpi-card label="Questions" :value="count($questions)" accent="cyan" />
                        <x-ui.kpi-card label="Livrables" :value="count($deliverables)" accent="yellow" />
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
