<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-8 px-0 py-2">
        <div>
            <p class="dgcpt-card-title">Analyse de l’IA</p>
            <h1 class="dgcpt-page-title">Observabilité copilote</h1>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <x-dgcpt.stat label="Exécutions" :value="$monitoring['executions'] ?? 0" />
            <x-dgcpt.stat label="Échecs" :value="$monitoring['failed'] ?? 0" accent="#FF5A5A" />
            <x-dgcpt.stat label="Latence moy." :value="($monitoring['avg_latency_ms'] ?? 0).' ms'" />
        </div>

        <x-dgcpt.card title="Usage" subtitle="Conversations & validations en attente">
            <p class="text-sm text-[#D7E2F2]">Conversations : {{ $usage['conversations'] ?? 0 }}</p>
            <p class="text-sm text-[#D7E2F2]">Recommandations : {{ $usage['recommendations'] ?? 0 }}</p>
            <p class="text-sm text-[#F4D000]">En attente validation humaine : {{ $usage['pending_validation'] ?? 0 }}</p>
        </x-dgcpt.card>

        <x-dgcpt.card title="Drivers" subtitle="Performance par fournisseur LLM">
            <pre class="text-xs text-[#9FB3C8]">{{ json_encode($performance, JSON_PRETTY_PRINT) }}</pre>
        </x-dgcpt.card>
    </div>
</x-app-layout>
