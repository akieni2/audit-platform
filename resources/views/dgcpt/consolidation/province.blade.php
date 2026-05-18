<x-app-layout>
    <motion.div class="mx-auto max-w-7xl space-y-8 px-0 py-2" data-motion="fade">
        <motion.div class="flex items-center justify-between gap-4" data-motion="fade">
            <motion.div data-motion="fade">
                <p class="dgcpt-card-title">{{ $entity->code }}</p>
                <h1 class="dgcpt-page-title">{{ $entity->name }}</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">{{ $entity->province }} · {{ $entity->entityTypeLabel() }}</p>
            </motion.div>
            <a href="{{ route('dgcpt.consolidation.national') }}" class="dgcpt-btn-outline">Vue nationale</a>
        </motion.div>

        <div class="grid gap-4 sm:grid-cols-3">
            <motion.div class="dgcpt-surface p-5" data-motion="fade">
                <p class="text-xs uppercase text-[#9FB3C8]">Missions</p>
                <p class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ $snapshot['missions_count'] }}</p>
            </motion.div>
            <motion.div class="dgcpt-surface p-5" data-motion="fade">
                <p class="text-xs uppercase text-[#9FB3C8]">Risques</p>
                <p class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ $snapshot['risks_count'] }}</p>
            </motion.div>
            <motion.div class="dgcpt-surface p-5" data-motion="fade">
                <p class="text-xs uppercase text-[#9FB3C8]">Risques critiques</p>
                <p class="mt-2 text-3xl font-bold text-[#FFB4B4]">{{ $snapshot['critical_risks'] }}</p>
            </motion.div>
        </motion.div>

        <motion.div class="dgcpt-surface p-6" data-motion="fade">
            <h2 class="text-lg font-bold text-[#E6EEF8]">Services auditables</h2>
            <ul class="mt-3 space-y-2 text-sm text-[#BFD2E6]">
                @foreach ($snapshot['services'] as $service)
                    <li>{{ $service->code }} — {{ $service->name }} ({{ $service->service_type }})</li>
                @endforeach
            </ul>
        </motion.div>
    </motion.div>
</x-app-layout>
