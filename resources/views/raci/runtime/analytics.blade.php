<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-8 px-0 py-2">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">RACI Analytics</p>
                <h1 class="dgcpt-page-title">{{ $mission->organisation }}</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">Surcharge, gaps organisationnels et pilotage des responsabilites.</p>
            </div>
            <a href="{{ route('raci.show', $mission) }}" class="dgcpt-btn-outline">Retour runtime</a>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            @foreach ($raciView['kpis'] as $label => $value)
                <div class="dgcpt-surface p-4 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-[#73D8FF]">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $label)) }}</p>
                    <p class="mt-2 text-2xl font-bold text-[#E6EEF8]">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="dgcpt-surface p-6 shadow-sm">
                <p class="dgcpt-card-title">Heatmap surcharge</p>
                <div class="mt-4 space-y-3">
                    @forelse ($raciView['overload'] as $row)
                        <div class="rounded-2xl bg-[rgba(255,255,255,0.03)] p-4">
                            <p class="text-sm font-semibold text-[#E6EEF8]">Utilisateur #{{ $row['assigned_user_id'] ?: 'n/a' }}</p>
                            <p class="mt-1 text-xs text-[#9FB3C8]">Affectations: {{ $row['count'] }} · critiques: {{ $row['critical'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-[#9FB3C8]">Aucune surcharge detectee.</p>
                    @endforelse
                </div>
            </div>

            <div class="dgcpt-surface p-6 shadow-sm">
                <p class="dgcpt-card-title">Processus couverts</p>
                <div class="mt-4 space-y-3">
                    @forelse ($raciView['processes'] as $process)
                        <div class="rounded-2xl bg-[rgba(255,255,255,0.03)] p-4">
                            <p class="text-sm font-semibold text-[#E6EEF8]">{{ $process['process_label'] }}</p>
                            <p class="mt-1 text-xs text-[#9FB3C8]">{{ $process['count'] }} affectations</p>
                        </div>
                    @empty
                        <p class="text-sm text-[#9FB3C8]">Aucun processus cartographie.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
