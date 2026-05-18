<x-app-layout>
    <motion.div class="mx-auto max-w-7xl space-y-8 px-0 py-2" data-motion="fade">
        <motion.div class="flex items-center justify-between gap-4" data-motion="fade">
            <motion.div data-motion="fade">
                <p class="dgcpt-card-title">Consolidation</p>
                <h1 class="dgcpt-page-title">Vue nationale DGCPT</h1>
            </motion.div>
            <a href="{{ route('dgcpt.hierarchy.index') }}" class="dgcpt-btn-outline">Hiérarchie</a>
        </motion.div>

        <div class="grid gap-4 sm:grid-cols-3">
            <motion.div class="dgcpt-surface p-5" data-motion="fade">
                <p class="text-xs uppercase text-[#9FB3C8]">Missions</p>
                <p class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ $snapshot['totals']['missions'] }}</p>
            </motion.div>
            <motion.div class="dgcpt-surface p-5" data-motion="fade">
                <p class="text-xs uppercase text-[#9FB3C8]">Missions contextualisées</p>
                <p class="mt-2 text-3xl font-bold text-[#7EF2BE]">{{ $snapshot['totals']['missions_contextualized'] }}</p>
            </motion.div>
            <motion.div class="dgcpt-surface p-5" data-motion="fade">
                <p class="text-xs uppercase text-[#9FB3C8]">Entités actives</p>
                <p class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ $snapshot['totals']['entities'] }}</p>
            </motion.div>
        </div>

        <motion.div class="dgcpt-surface p-6" data-motion="fade">
            <h2 class="text-lg font-bold text-[#E6EEF8]">Provinces</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-[rgba(0,209,255,0.10)] text-left text-[#9FB3C8]">
                            <th class="px-3 py-2">Code</th>
                            <th class="px-3 py-2">Entité</th>
                            <th class="px-3 py-2">Province</th>
                            <th class="px-3 py-2">Missions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($snapshot['provinces'] as $row)
                            <tr class="border-b border-[rgba(255,255,255,0.04)] text-[#BFD2E6]">
                                <td class="px-3 py-2 font-mono text-[#73D8FF]">{{ $row['code'] }}</td>
                                <td class="px-3 py-2">{{ $row['name'] }}</td>
                                <td class="px-3 py-2">{{ $row['province'] }}</td>
                                <td class="px-3 py-2">{{ $row['missions_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </motion.div>
    </motion.div>
</x-app-layout>
