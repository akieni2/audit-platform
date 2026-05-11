<x-app-layout>
    @php
        $usersActive = \App\Models\User::query()->where('active', true)->count();
        $perfRate = $missions > 0 ? min(100, (int) round(($missionsValideesNationales / $missions) * 100)) : 0;
        $conformite = $risques > 0 ? max(0, min(100, (int) round(100 - ($risquesCritiques / $risques) * 100))) : 100;
    @endphp

    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <header class="space-y-2">
            <p class="text-[0.65rem] font-bold uppercase tracking-[0.2em] text-dgcpt-cyan/90">Pilotage d?partemental</p>
            <h1 class="text-2xl font-extrabold uppercase tracking-wide text-slate-900 dark:text-white">Centre de contr?le ? tableau de bord</h1>
        </header>

        @if(isset($departments) && $departments->isNotEmpty())
            <x-ui.dashboard-panel>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Rattachement :
                    @if(auth()->user()?->department)
                        <strong class="font-mono text-slate-900 dark:text-slate-100">{{ auth()->user()->department->code }}</strong>
                        <span class="text-slate-500">?</span> {{ auth()->user()->department->name }}
                    @else
                        <em>non d?fini</em>
                    @endif
                </p>

                @if(!empty($focusedDepartment) && auth()->user()?->canViewAllInstitutionalData())
                    <div class="mt-3 rounded-lg border border-dgcpt-cyan/25 bg-dgcpt-blue/20 px-3 py-2 text-sm text-slate-100">
                        <strong>Vue p?le :</strong> {{ $focusedDepartment->code }} ? {{ $focusedDepartment->name }}.
                        Indicateurs <em>limit?s ? ce d?partement</em>.
                        <a href="{{ route('dashboard', ['department' => 'all']) }}" class="ml-2 font-semibold text-dgcpt-cyan underline">Vue globale</a>
                    </div>
                @endif

                <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                    @if(auth()->user()?->canViewAllInstitutionalData())
                        Filtre tableau de bord par p?le ; les menus restent globaux.
                    @else
                        Raccourci ?quivalent au p?rim?tre missions de votre p?le.
                    @endif
                </p>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($departments as $dept)
                        @php $isFocus = isset($dashboardDepartmentFocusId) && (int) $dashboardDepartmentFocusId === (int) $dept->id; @endphp
                        <a href="{{ route('dashboard', ['department' => $dept->id]) }}"
                           class="inline-flex items-center gap-1 rounded-xl px-3 py-1.5 text-sm font-semibold transition
                               {{ $isFocus ? 'bg-dgcpt-cyan/20 text-white ring-1 ring-dgcpt-cyan/50' : 'bg-slate-200/80 text-slate-800 hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700' }}">
                            <span class="font-mono">{{ $dept->code }}</span>
                            <span class="opacity-90">{{ \Illuminate\Support\Str::limit($dept->name, 36) }}</span>
                        </a>
                    @endforeach
                    @if(auth()->user()?->canViewAllInstitutionalData())
                        <a href="{{ route('dashboard', ['department' => 'all']) }}"
                           class="inline-flex rounded-xl border border-dgcpt-cyan/30 px-3 py-1.5 text-sm text-dgcpt-cyan hover:bg-dgcpt-cyan/10">
                            Vue globale
                        </a>
                    @endif
                </div>
            </x-ui.dashboard-panel>
        @endif

        <div class="dgcpt-kpi-grid">
            <x-ui.kpi-card label="Audits en cours" :value="$missionsEnCours" accent="cyan" />
            <x-ui.kpi-card label="Risques critiques" :value="$risquesCritiques" accent="danger" />
            <x-ui.kpi-card label="Missions valid?es" :value="$missionsValideesNationales" accent="green" />
            <x-ui.kpi-card label="Conformit? (synth?se)" :value="$conformite.'%'" accent="yellow" />
            <x-ui.kpi-card label="Utilisateurs actifs" :value="$usersActive" accent="violet" />
            <x-ui.kpi-card label="Actions correctives ouvertes" :value="$actionsOuvertes" accent="cyan" />
            <x-ui.kpi-card label="Taux de validation" :value="$perfRate.'%'" accent="green" />
        </div>

        @if(isset($missionsEnCours))
            <div class="dgcpt-kpi-grid">
                <x-ui.kpi-card label="Missions (total)" :value="$missions" />
                <x-ui.kpi-card label="Brouillons" :value="$missionsBrouillon" />
                <x-ui.kpi-card label="Entretiens terrain" :value="$entretiensTerrain" accent="violet" />
                <x-ui.kpi-card label="Actions en retard" :value="$actionsRetard" accent="danger" />
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <x-ui.chart-card title="Cr?ation de missions (12 semaines)" subtitle="Volume hebdomadaire sur votre p?rim?tre.">
                <canvas id="missionTrendChart"></canvas>
            </x-ui.chart-card>
            <x-ui.chart-card title="R?partition des risques par service" subtitle="Comptage agr?g?.">
                <canvas id="riskChart"></canvas>
            </x-ui.chart-card>
        </div>

        <div class="rounded-xl border px-4 py-3 text-sm font-semibold {{ $risquesCritiques > 0 ? 'border-red-500/40 bg-red-950/40 text-red-100' : 'border-dgcpt-green/35 bg-dgcpt-green/10 text-emerald-100' }}">
            @if($risquesCritiques > 0)
                Alerte : {{ $risquesCritiques }} risque(s) critique(s) sur le p?rim?tre.
            @else
                Situation ma?tris?e : aucun risque critique r?siduel signal? sur ce p?rim?tre.
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof Chart === 'undefined') return;

                const trendLabels = @json($missionTrendLabels ?? []);
                const trendData = @json($missionTrendCounts ?? []);

                new Chart(document.getElementById('missionTrendChart'), {
                    type: 'line',
                    data: {
                        labels: trendLabels,
                        datasets: [{
                            label: 'Missions cr??es',
                            data: trendData,
                            borderColor: '#00D1FF',
                            backgroundColor: 'rgba(0, 209, 255, 0.12)',
                            fill: true,
                            tension: 0.35,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(148,163,184,0.15)' } },
                            x: { grid: { display: false } },
                        },
                    },
                });

                const riskLabels = @json(collect($services ?? [])->pluck('nom'));
                const riskData = @json(collect($services ?? [])->map(fn ($s) => (int) ($s->risques_count ?? 0))->values());

                new Chart(document.getElementById('riskChart'), {
                    type: 'bar',
                    data: {
                        labels: riskLabels,
                        datasets: [{
                            label: 'Risques',
                            data: riskData,
                            backgroundColor: '#0A2A66',
                            borderColor: '#00A86B',
                            borderWidth: 1,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(148,163,184,0.15)' } },
                            x: { grid: { display: false } },
                        },
                    },
                });
            });
        </script>
    @endpush
</x-app-layout>
