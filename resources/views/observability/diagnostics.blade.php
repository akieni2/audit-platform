<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <motion.div data-dgcpt-motion="fade-up">
            <p class="dgcpt-card-title">Diagnostic d’exécution</p>
            <h1 class="dgcpt-page-title">Diagnostics d’exécution</h1>
        </motion.div>

        <x-dgcpt.card title="Mission courante" subtitle="Orphelins & workflow">
            @if ($mission)
                <pre class="overflow-x-auto rounded-lg bg-[#0B1F33] p-4 text-xs text-[#D7E2F2]">{{ json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @else
                <p class="text-sm text-[#9FB3C8]">{{ $diagnostics['message'] ?? 'Aucune donnée.' }}</p>
            @endif
        </x-dgcpt.card>
    </motion.div>
</x-app-layout>
