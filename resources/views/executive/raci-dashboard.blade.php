<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <div>
            <p class="dgcpt-card-title">Executive RACI</p>
            <h1 class="dgcpt-page-title">RACI Dashboard</h1>
            <p class="mt-1 text-sm text-[#9FB3C8]">Conflits organisationnels, heatmap de responsabilites et gaps multi-departements.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            @foreach ($dashboard['snapshot']['kpis'] as $label => $value)
                <div class="dgcpt-surface p-4 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-[#73D8FF]">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $label)) }}</p>
                    <p class="mt-2 text-2xl font-bold text-[#E6EEF8]">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="dgcpt-surface p-6 shadow-sm">
                <p class="dgcpt-card-title">Conflicts</p>
                <div class="mt-4 space-y-3">
                    @forelse ($dashboard['conflicts']['conflicts'] as $conflict)
                        <div class="rounded-2xl bg-[rgba(255,255,255,0.03)] p-4">
                            <p class="text-sm font-semibold text-[#E6EEF8]">{{ $conflict['key'] }}</p>
                            <p class="mt-1 text-xs text-[#9FB3C8]">Accountables: {{ $conflict['accountables'] }} · Affectations: {{ $conflict['assignments'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-[#9FB3C8]">Aucun conflit detecte.</p>
                    @endforelse
                </div>
            </div>

            <div class="dgcpt-surface p-6 shadow-sm">
                <p class="dgcpt-card-title">Heatmap</p>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    @foreach ($dashboard['heatmap'] as $row)
                        <div class="rounded-2xl bg-[rgba(255,255,255,0.03)] p-4">
                            <p class="text-xs uppercase tracking-wide text-[#73D8FF]">{{ $row['status'] }}</p>
                            <p class="mt-2 text-2xl font-bold text-[#E6EEF8]">{{ $row['count'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="dgcpt-surface p-6 shadow-sm">
            <p class="dgcpt-card-title">Gaps</p>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                @foreach ($dashboard['gaps']['totals'] as $label => $value)
                    <div class="rounded-2xl bg-[rgba(255,255,255,0.03)] p-4">
                        <p class="text-xs uppercase tracking-wide text-[#73D8FF]">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $label)) }}</p>
                        <p class="mt-2 text-2xl font-bold text-[#E6EEF8]">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
