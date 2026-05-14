<x-dgcpt.card title="Alertes" subtitle="Signals & escalations">
    <div class="space-y-3">
        @foreach ($dashboardUx['alerts'] as $alert)
            <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-[#E6EEF8]">{{ $alert['title'] }}</p>
                    <x-dgcpt.badge :label="strtoupper($alert['tone'])" :tone="$alert['tone']" />
                </div>
                <p class="mt-2 text-sm text-[#9FB3C8]">{{ $alert['message'] }}</p>
            </div>
        @endforeach
    </div>
</x-dgcpt.card>
