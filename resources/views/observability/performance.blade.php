<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <motion.div data-dgcpt-motion="fade-up">
            <p class="dgcpt-card-title">Performance</p>
            <h1 class="dgcpt-page-title">Cache & requêtes</h1>
        </motion.div>

        <x-dgcpt.card title="Analytics cache" subtitle="TTL {{ $analytics['cache_ttl'] ?? 600 }}s">
            <p class="text-sm text-[#9FB3C8]">Cache analytics : {{ ($analytics['warmed'] ?? false) ? 'actif' : 'froid' }}</p>
        </x-dgcpt.card>

        @if (count($slowQueries) > 0)
            <x-dgcpt.card title="Requêtes lentes" subtitle="Profiling local">
                <pre class="overflow-x-auto text-xs text-[#D7E2F2]">{{ json_encode($slowQueries, JSON_PRETTY_PRINT) }}</pre>
            </x-dgcpt.card>
        @endif
    </div>
</x-app-layout>