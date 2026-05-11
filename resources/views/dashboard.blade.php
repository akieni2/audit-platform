<x-app-layout>
    @php
        $usersActive = \App\Models\User::query()->where('active', true)->count();
        $perfRate = $missions > 0 ? min(100, (int) round(($missionsValideesNationales / $missions) * 100)) : 0;
        $conformite = $risques > 0 ? max(0, min(100, (int) round(100 - ($risquesCritiques / $risques) * 100))) : 100;
    @endphp

    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <header class="space-y-2">
            <p class="dgcpt-card-title">Pilotage départemental</p>
            <h1 class="dgcpt-page-title">Centre de contrôle — tableau de bord</h1>
        </header>

        @if(isset($departments) && $departments->isNotEmpty())
            <x-ui.dashboard-panel>
                <p class="text-sm text-[#9FB3C8]">
                    Rattachement :
                    @if(auth()->user()?->department)
                        <strong class="font-mono text-[#E6EEF8]">{{ auth()->user()->department->code }}</strong>
                        <span class="text-[#9FB3C8]"> — </span> {{ auth()->user()->department->name }}
                    @else
                        <em class="text-[#9FB3C8]">non défini</em>
                    @endif
                </p>

                @if(!empty($focusedDepartment) && auth()->user()?->canViewAllInstitutionalData())
                    <div class="mt-3 rounded-lg border border-[rgba(0,209,255,0.28)] bg-[#10192B] px-3 py-2 text-sm text-[#E6EEF8]">
                        <strong class="text-[#00D1FF]">Vue pôle :</strong> {{ $focusedDepartment->code }} — {{ $focusedDepartment->name }}.
                        Indicateurs <em class="text-[#9FB3C8]">limités à ce département</em>.
                        <a href="{{ route('dashboard', ['department' => 'all']) }}" class="ml-2 font-semibold text-[#00D1FF] underline underline-offset-2 hover:text-[#E6EEF8]">Vue globale</a>
                    </div>
                @endif

                <p class="mt-3 text-xs text-[#9FB3C8]">
                    @if(auth()->user()?->canViewAllInstitutionalData())
                        Filtre tableau de bord par pôle ; les menus restent globaux.
                    @else
                        Raccourci équivalent au périmètre missions de votre pôle.
                    @endif
                </p>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($departments as $dept)
                        @php $isFocus = isset($dashboardDepartmentFocusId) && (int) $dashboardDepartmentFocusId === (int) $dept->id; @endphp
                        <a href="{{ route('dashboard', ['department' => $dept->id]) }}"
                           class="inline-flex items-center gap-1 rounded-xl px-3 py-1.5 text-sm font-semibold transition
                               {{ $isFocus ? 'bg-[#122038] text-[#E6EEF8] ring-2 ring-[#00D1FF] ring-offset-2 ring-offset-[#0B1220]' : 'border border-[rgba(0,209,255,0.18)] bg-[#0B1220] text-[#E6EEF8] hover:border-[rgba(0,209,255,0.35)] hover:bg-[#122038]' }}">
                            <span class="font-mono">{{ $dept->code }}</span>
                            <span class="text-[#9FB3C8]">{{ \Illuminate\Support\Str::limit($dept->name, 36) }}</span>
                        </a>
                    @endforeach
                    @if(auth()->user()?->canViewAllInstitutionalData())
                        <a href="{{ route('dashboard', ['department' => 'all']) }}"
                           class="inline-flex rounded-xl border border-[rgba(0,209,255,0.35)] px-3 py-1.5 text-sm font-semibold text-[#00D1FF] hover:bg-[#122038]">
                            Vue globale
                        </a>
                    @endif
                </div>
            </x-ui.dashboard-panel>
        @endif

        <div class="dgcpt-kpi-grid">
            <x-ui.kpi-card label="Audits en cours" :value="$missionsEnCours" accent="cyan" />
            <x-ui.kpi-card label="Risques critiques" :value="$risquesCritiques" accent="danger" />
            <x-ui.kpi-card label="Missions validées" :value="$missionsValideesNationales" accent="green" />
            <x-ui.kpi-card label="Conformité (synthèse)" :value="$conformite.'%'" accent="yellow" />
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
            <x-ui.chart-card title="Création de missions (12 semaines)" subtitle="Volume hebdomadaire sur votre périmètre.">
                <canvas id="missionTrendChart"></canvas>
            </x-ui.chart-card>
            <x-ui.chart-card title="Répartition des risques par service" subtitle="Comptage agrégé.">
                <canvas id="riskChart"></canvas>
            </x-ui.chart-card>
        </div>

        <div class="rounded-xl border px-4 py-3 text-sm font-semibold {{ $risquesCritiques > 0 ? 'border-[rgba(255,90,90,0.45)] bg-[#10192B] text-[#FF5A5A]' : 'border-[rgba(0,168,107,0.4)] bg-[#10192B] text-[#E6EEF8]' }}">
            @if($risquesCritiques > 0)
                Alerte : {{ $risquesCritiques }} risque(s) critique(s) sur le périmètre.
            @else
                Situation maîtrisée : aucun risque critique résiduel signalé sur ce périmètre.
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
                            label: 'Missions créées',
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
