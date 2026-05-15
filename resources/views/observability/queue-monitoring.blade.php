<x-app-layout>
    <motion.div class="mx-auto max-w-7xl space-y-8 px-0 py-2" data-dgcpt-motion="fade-up">
        <motion.div data-dgcpt-motion="fade-up">
            <p class="dgcpt-card-title">Queue Monitoring</p>
            <h1 class="dgcpt-page-title">Files & throughput</h1>
        </motion.div>

        <x-dgcpt.card title="État des files" subtitle="{{ $queueHealth['connection'] ?? 'database' }}">
            <motion.div class="grid gap-4 md:grid-cols-4" data-dgcpt-motion="stagger">
                <x-dgcpt.stat label="En attente" :value="$queueHealth['pending_jobs'] ?? 0" />
                <x-dgcpt.stat label="Échoués" :value="$queueHealth['failed_jobs'] ?? 0" accent="#FF5A5A" />
                <x-dgcpt.stat label="Projection queue" :value="$queueHealth['projection_queue'] ?? '—'" />
                <x-dgcpt.stat label="Sain" :value="($queueHealth['healthy'] ?? false) ? 'Oui' : 'Non'" accent="#00A86B" />
            </motion.div>
        </x-dgcpt.card>
    </motion.div>
</x-app-layout>
