<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-8 px-0 py-2">
        <header class="space-y-2">
            <p class="dgcpt-card-title">Executive Analytics Platform</p>
            <h1 class="dgcpt-page-title">Maturity Index</h1>
            <p class="text-sm text-[#9FB3C8]">Indice de maturité de gouvernance par département à partir du portefeuille risques et du niveau de contrôle.</p>
        </header>

        <div class="dgcpt-surface p-6">
            <div class="space-y-3">
                @foreach (($maturity['departments'] ?? []) as $row)
                    <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-[#E6EEF8]">{{ $row['department'] }}</p>
                                <p class="mt-1 text-xs text-[#9FB3C8]">Registry: {{ $row['registry_count'] }} · Intake: {{ $row['intake_count'] }} · Critiques: {{ $row['critical_open'] }}</p>
                            </div>
                            <span class="rounded-full bg-[rgba(0,209,255,0.12)] px-3 py-1 text-sm font-semibold text-[#73D8FF]">
                                {{ $row['maturity_score'] }}/100
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
