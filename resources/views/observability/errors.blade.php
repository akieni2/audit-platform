<x-dgcpt.card title="Erreurs" subtitle="Agrégation visuelle">
    <div class="space-y-3">
        @forelse ($errorSummary['items'] as $item)
            <div class="rounded-2xl border border-[rgba(255,90,90,0.20)] bg-[rgba(58,26,32,0.45)] p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-[#FFD4D4]">{{ $item['title'] }}</p>
                    <x-dgcpt.badge :label="strtoupper($item['source'])" tone="danger" />
                </div>
                <p class="mt-2 text-sm text-[#FFD4D4]">{{ $item['message'] }}</p>
            </div>
        @empty
            <p class="text-sm text-[#9FB3C8]">Aucune erreur consolidée.</p>
        @endforelse
    </div>
</x-dgcpt.card>
