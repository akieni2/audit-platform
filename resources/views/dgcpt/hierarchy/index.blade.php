<x-app-layout>
    <motion.div class="mx-auto max-w-7xl space-y-8 px-0 py-2" data-motion="fade">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Phase métier DGCPT</p>
                <h1 class="dgcpt-page-title">Hiérarchie Trésor public</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">National → provincial → départemental → international</p>
            </motion.div>
            <a href="{{ route('dgcpt.consolidation.national') }}" class="dgcpt-btn-primary">Consolidation nationale</a>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.2fr,0.8fr]">
            <div class="dgcpt-surface p-6">
                <h2 class="text-lg font-bold text-[#E6EEF8]">Arbre organisationnel</h2>
                <div class="mt-4 space-y-3 text-sm">
                    @forelse ($tree as $root)
                        @include('dgcpt.hierarchy.partials.node', ['node' => $root, 'depth' => 0])
                    @empty
                        <p class="text-[#9FB3C8]">Aucune entité. Exécutez le seeder DGCPT.</p>
                    @endforelse
                </div>
            </div>

            <div class="space-y-6">
                <motion.div class="dgcpt-surface p-6" data-motion="fade">
                    <h2 class="text-lg font-bold text-[#E6EEF8]">Domaines d'audit</h2>
                    <ul class="mt-3 space-y-2 text-sm text-[#BFD2E6]">
                        @foreach ($domains as $domain)
                            <li><span class="font-mono text-[#73D8FF]">{{ $domain->code }}</span> — {{ $domain->name }}</li>
                        @endforeach
                    </ul>
                </motion.div>

                <motion.div class="dgcpt-surface p-6" data-motion="fade">
                    <h2 class="text-lg font-bold text-[#E6EEF8]">Templates nationaux</h2>
                    <ul class="mt-3 space-y-3 text-sm">
                        @foreach ($templates as $template)
                            <li class="rounded border border-[rgba(0,209,255,0.12)] p-3 text-[#BFD2E6]">
                                <p class="font-semibold text-[#E6EEF8]">{{ $template->name }}</p>
                                <p class="text-xs text-[#9FB3C8]">{{ $template->code }} · {{ $template->auditDomain?->name }}</p>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('dgcpt.questionnaire-import.index') }}" class="dgcpt-btn-outline mt-4 inline-block">Importer questionnaire (DOCX/XLSX)</a>
                </motion.div>
            </div>
        </div>
    </motion.div>
</x-app-layout>
