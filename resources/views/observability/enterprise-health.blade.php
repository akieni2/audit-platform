<x-app-layout>
    <motion.div class="mx-auto max-w-7xl space-y-8 px-0 py-2" data-dgcpt-motion="fade-up">
        <motion.div class="flex flex-wrap items-end justify-between gap-4" data-dgcpt-motion="fade-up">
            <div>
                <p class="dgcpt-card-title">Enterprise Health</p>
                <h1 class="dgcpt-page-title">Santé plateforme</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">Tenant scope, files, intégrité audit immutable.</p>
            </motion.div>
            <a href="{{ route('workflow-runtime.observability') }}" class="dgcpt-btn-outline">Centre observability</a>
        </motion.div>

        <x-dgcpt.card title="Snapshot" subtitle="Tenant {{ $health['tenant_scope'] ?? '—' }}">
            <motion.div class="grid gap-4 md:grid-cols-3" data-dgcpt-motion="stagger">
                <x-dgcpt.stat label="Tenant key" :value="$health['tenant_key'] ?? 'national'" />
                <x-dgcpt.stat label="Jobs en attente" :value="$health['queues']['pending_jobs'] ?? 0" accent="#F4D000" />
                <x-dgcpt.stat label="Jobs échoués" :value="$health['queues']['failed_jobs'] ?? 0" accent="#FF5A5A" />
            </motion.div>
            <p class="mt-4 text-sm text-[#9FB3C8]">
                Chaîne audit vérifiée :
                {{ ($health['audit_integrity']['verified'] ?? false) ? 'OK' : 'ALERTE' }}
                ({{ $health['audit_integrity']['checked'] ?? 0 }} événements)
            </p>
        </x-dgcpt.card>
    </motion.div>
</x-app-layout>
