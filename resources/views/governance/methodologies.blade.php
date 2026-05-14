<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <header class="space-y-2">
            <p class="dgcpt-card-title">Multi-Methodology Engine</p>
            <h1 class="dgcpt-page-title">Méthodologies enterprise</h1>
            <p class="text-sm text-[#9FB3C8]">Référentiels multi-frameworks, mappings workflow, formulaires, questionnaires et contrôles réutilisables.</p>
        </header>

        <div class="grid gap-6 xl:grid-cols-2">
            @foreach ($methodologies as $methodology)
                <div class="dgcpt-surface p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-lg font-bold text-[#E6EEF8]">{{ $methodology->name }}</p>
                            <p class="mt-1 text-xs uppercase tracking-wide text-[#73D8FF]">{{ $methodology->framework_key }} · {{ $methodology->lifecycleLabel() }}</p>
                        </div>
                        <span class="rounded-full bg-[rgba(0,209,255,0.12)] px-3 py-1 text-xs font-semibold text-[#73D8FF]">v{{ $methodology->version }}</span>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-4">
                        <x-ui.kpi-card label="Catégories" :value="$methodology->categories_count" accent="cyan" />
                        <x-ui.kpi-card label="Contrôles" :value="$methodology->controls_count" accent="green" />
                        <x-ui.kpi-card label="Exigences" :value="$methodology->requirements_count" accent="yellow" />
                        <x-ui.kpi-card label="Mappings" :value="$methodology->mappings_count" accent="violet" />
                    </div>

                    @if (isset($methodologyStacks[$methodology->id]))
                        <div class="mt-4 text-xs text-[#BFD2E6]">
                            <p>Workflows liés: {{ $methodologyStacks[$methodology->id]['workflows']->count() }}</p>
                            <p>Formulaires liés: {{ $methodologyStacks[$methodology->id]['forms']->count() }}</p>
                            <p>Questionnaires liés: {{ $methodologyStacks[$methodology->id]['questionnaires']->count() }}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
