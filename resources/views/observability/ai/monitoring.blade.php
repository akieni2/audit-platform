<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-8 px-0 py-2">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">AI Observability</p>
                <h1 class="dgcpt-page-title">Monitoring IA enterprise</h1>
            </div>
            <a href="{{ route('ai.analytics') }}" class="dgcpt-btn-outline">Analytics IA</a>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <x-dgcpt.stat label="Exécutions" :value="$monitoring['executions'] ?? 0" />
            <x-dgcpt.stat label="Sain" :value="($monitoring['healthy'] ?? false) ? 'Oui' : 'Non'" accent="#00A86B" />
            <x-dgcpt.stat label="Conversations" :value="$usage['conversations'] ?? 0" />
            <x-dgcpt.stat label="Pending validation" :value="$usage['pending_validation'] ?? 0" accent="#F4D000" />
        </div>
    </div>
</x-app-layout>
