<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <header class="space-y-2">
            <p class="dgcpt-card-title">Taxonomy Engine</p>
            <h1 class="dgcpt-page-title">Taxonomies enterprise</h1>
            <p class="text-sm text-[#9FB3C8]">Taxonomies nationales et départementales pour risques, contrôles, workflows, questionnaires et formulaires.</p>
        </header>

        <div class="grid gap-6 xl:grid-cols-2">
            @foreach ($taxonomies as $taxonomy)
                <div class="dgcpt-surface p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-lg font-bold text-[#E6EEF8]">{{ $taxonomy->name }}</p>
                            <p class="mt-1 text-xs uppercase tracking-wide text-[#73D8FF]">{{ $taxonomy->taxonomy_type }}</p>
                        </div>
                        @if ($taxonomy->is_national)
                            <span class="rounded-full bg-[rgba(0,168,107,0.12)] px-3 py-1 text-xs font-semibold text-[#7EF2BE]">National</span>
                        @endif
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <x-ui.kpi-card label="Termes" :value="$taxonomy->terms_count" accent="cyan" />
                        <x-ui.kpi-card label="Mappings" :value="$taxonomy->mappings_count" accent="violet" />
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
