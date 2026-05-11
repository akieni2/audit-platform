<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-8 px-4 py-10">
        <header class="space-y-2">
            <p class="dgcpt-card-title">COPRI — pilotage stratégique</p>
            <h1 class="dgcpt-page-title">Indicateurs nationaux consolidés</h1>
            <p class="text-sm text-[#9FB3C8]">
                Vue agrégée uniquement : aucune donnée opérationnelle nominative n'est exposée à ce niveau.
            </p>
        </header>

        <div class="dgcpt-kpi-grid">
            @foreach ($kpis as $label => $value)
                <div class="dgcpt-kpi-card">
                    <div class="dgcpt-kpi-label">{{ str_replace('_', ' ', $label) }}</div>
                    <div class="dgcpt-kpi-value">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <div class="rounded-xl border border-[rgba(244,208,0,0.35)] bg-[#10192B] px-4 py-3 text-sm text-[#E6EEF8] shadow-[0_8px_28px_rgba(0,0,0,0.28)]">
            <span class="font-bold uppercase tracking-wider text-[#F4D000]">Note</span>
            <span class="text-[#9FB3C8]"> — </span>
            Les analyses de tendance et rapports consolidés institutionnels peuvent être branchés sur ces agrégats (exports sécurisés, périmètre COPRI).
        </div>

        <p class="text-sm text-[#9FB3C8]">
            <a href="{{ route('dashboard') }}" class="dgcpt-link">← Tableau de bord opérationnel (si habilité)</a>
        </p>
    </div>
</x-app-layout>
