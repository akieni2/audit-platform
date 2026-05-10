<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 py-8 space-y-8">
        <header class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400">Pilotage d?partemental</p>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Tableau de bord audit</h1>
        </header>

        @if(isset($departments) && $departments->isNotEmpty())
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-gray-800">
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
                    <div class="mt-3 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-900 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-100">
                        <strong>Vue p?le :</strong> {{ $focusedDepartment->code }} ? {{ $focusedDepartment->name }}.
                        Indicateurs <em>limit?s ? ce d?partement</em>.
                        <a href="{{ route('dashboard', ['department' => 'all']) }}" class="ml-2 font-semibold text-blue-700 underline dark:text-blue-300">Vue globale</a>
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
                           class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium transition
                               {{ $isFocus ? 'bg-indigo-600 text-white shadow' : 'bg-slate-100 text-slate-800 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600' }}">
                            <span class="font-mono">{{ $dept->code }}</span>
                            <span class="opacity-90">{{ \Illuminate\Support\Str::limit($dept->name, 36) }}</span>
                        </a>
                    @endforeach
                    @if(auth()->user()?->canViewAllInstitutionalData())
                        <a href="{{ route('dashboard', ['department' => 'all']) }}"
                           class="inline-flex rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800">
                            Vue globale
                        </a>
                    @endif
                </div>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl border border-slate-200 bg-slate-900 p-5 text-white shadow-sm dark:border-slate-700">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Missions</p>
                <p class="mt-2 text-4xl font-bold tabular-nums">{{ $missions }}</p>
            </div>
            <div class="rounded-xl border border-blue-100 bg-blue-600 p-5 text-white shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-blue-100">Risques</p>
                <p class="mt-2 text-4xl font-bold tabular-nums">{{ $risques }}</p>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-700 p-5 text-white shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-red-100">Risques critiques</p>
                <p class="mt-2 text-4xl font-bold tabular-nums">{{ $risquesCritiques }}</p>
            </div>
            <div class="rounded-xl border border-orange-200 bg-orange-500 p-5 text-white shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-orange-50">Actions ouvertes</p>
                <p class="mt-2 text-4xl font-bold tabular-nums">{{ $actionsOuvertes }}</p>
            </div>
            <div class="rounded-xl border border-slate-700 bg-slate-950 p-5 text-white shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Actions en retard</p>
                <p class="mt-2 text-4xl font-bold tabular-nums">{{ $actionsRetard }}</p>
            </div>
        </div>

        @if(isset($missionsEnCours))
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl bg-sky-500 p-4 text-white shadow">
                    <p class="text-xs opacity-90">Missions en cours</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums">{{ $missionsEnCours }}</p>
                </div>
                <div class="rounded-xl bg-slate-500 p-4 text-white shadow">
                    <p class="text-xs opacity-90">Brouillons</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums">{{ $missionsBrouillon }}</p>
                </div>
                <div class="rounded-xl bg-emerald-600 p-4 text-white shadow">
                    <p class="text-xs opacity-90">Valid?es IS / COPRI</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums">{{ $missionsValideesNationales }}</p>
                </div>
                <div class="rounded-xl bg-violet-600 p-4 text-white shadow">
                    <p class="text-xs opacity-90">Entretiens terrain</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums">{{ $entretiensTerrain }}</p>
                </div>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Cr?ation de missions (12 semaines)</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Volume hebdomadaire sur votre p?rim?tre.</p>
                <div class="mt-4 h-64">
                    <canvas id="missionTrendChart"></canvas>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">R?partition des risques par service</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comptage agr?g?.</p>
                <div class="mt-4 h-64">
                    <canvas id="riskChart"></canvas>
                </div>
            </div>
        </div>

        <div class="rounded-lg border px-4 py-3 text-sm font-medium {{ $risquesCritiques > 0 ? 'border-red-300 bg-red-50 text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-100' : 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-100' }}">
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
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.12)',
                            fill: true,
                            tension: 0.35,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } },
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
                            backgroundColor: '#2563eb',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    },
                });
            });
        </script>
    @endpush
</x-app-layout>
