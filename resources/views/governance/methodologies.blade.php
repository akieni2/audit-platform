<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <header class="space-y-2">
            <p class="dgcpt-card-title">Catalogue institutionnel DGCPT</p>
            <h1 class="dgcpt-page-title">Référentiels d’audit homologués</h1>
            <p class="text-sm text-[#9FB3C8]">Référentiels adoptés, procédures générées, livrables attendus, bibliothèques de questions et mappings vers la taxonomie commune des risques.</p>
        </header>

        <div class="grid gap-6 xl:grid-cols-2">
            @foreach ($methodologies as $methodology)
                @php($procedure = $procedureSummaries[$methodology->id] ?? null)
                <div class="dgcpt-surface p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-lg font-bold text-[#E6EEF8]">{{ $methodology->name }}</p>
                            <p class="mt-1 text-xs uppercase tracking-wide text-[#73D8FF]">{{ $methodology->framework_key }} · {{ $methodology->lifecycleLabel() }}</p>
                        </div>
                        <span class="rounded-full bg-[rgba(0,209,255,0.12)] px-3 py-1 text-xs font-semibold text-[#73D8FF]">v{{ $methodology->version }}</span>
                    </div>

                    <p class="mt-3 text-sm text-[#BFD2E6]">{{ $methodology->description }}</p>

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

                    @if ($procedure)
                        <div class="mt-5 rounded-lg border border-[rgba(0,209,255,0.12)] bg-[rgba(7,18,32,0.62)] p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-[#E6EEF8]">Procédure officielle proposée</p>
                                <span class="text-xs uppercase tracking-wide text-[#73D8FF]">{{ count($procedure['stages']) }} étapes · {{ count($procedure['deliverables']) }} livrables · {{ count($procedure['questions']) }} questions</span>
                            </div>

                            <div class="mt-4 space-y-3">
                                @foreach (array_slice($procedure['stages'], 0, 5) as $stage)
                                    <div class="rounded-md border border-[rgba(255,255,255,0.08)] p-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-[#E6EEF8]">{{ $stage['rank'] }}. {{ $stage['name'] }}</p>
                                                <p class="mt-1 text-xs text-[#9FB3C8]">{{ $stage['objective'] }}</p>
                                            </div>
                                            <span class="rounded-full bg-[rgba(126,242,190,0.1)] px-2 py-1 text-[11px] font-semibold text-[#7EF2BE]">{{ $stage['criticality'] }}</span>
                                        </div>
                                        <p class="mt-2 text-xs text-[#BFD2E6]">Livrables: {{ implode(', ', $stage['deliverables']) }}</p>
                                        @if (! empty($stage['questions']))
                                            <p class="mt-1 text-xs text-[#73D8FF]">{{ count($stage['questions']) }} questions types rattachées</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            @if (! empty($procedure['taxonomy_terms']))
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach ($procedure['taxonomy_terms'] as $term)
                                        <span class="rounded-full bg-[rgba(255,255,255,0.08)] px-3 py-1 text-xs text-[#BFD2E6]">{{ $term['code'] }} · {{ $term['name'] }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
