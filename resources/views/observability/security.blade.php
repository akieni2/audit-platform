<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <motion.div class="flex flex-wrap items-end justify-between gap-4" data-dgcpt-motion="fade-up">
            <motion.div data-dgcpt-motion="fade-up">
                <p class="dgcpt-card-title">Security Monitoring</p>
                <h1 class="dgcpt-page-title">Sécurité enterprise</h1>
            </motion.div>
            <a href="{{ route('admin.security.audit-logs') }}" class="dgcpt-btn-outline">Journal IAM</a>
        </motion.div>

        <x-dgcpt.card title="Intégrité audit" subtitle="Tamper detection">
            <p class="text-sm text-[#D7E2F2]">
                Vérifié : {{ ($integrity['verified'] ?? false) ? 'oui' : 'non' }}
                @if (! empty($forensics))
                    — {{ count($forensics) }} anomalie(s) détectée(s)
                @endif
            </p>
        </x-dgcpt.card>

        <x-dgcpt.card title="Événements sécurité runtime" subtitle="Dernières alertes">
            <div class="space-y-2 text-sm">
                @forelse ($events as $event)
                    <div class="rounded border border-[#1E3A5F] px-3 py-2">
                        <span class="font-medium text-[#D7E2F2]">{{ $event->event_type }}</span>
                        <span class="text-[#9FB3C8]"> — {{ $event->severity }}</span>
                        <span class="block text-xs text-[#6B8299]">{{ $event->occurred_at?->format('d/m/Y H:i') }}</span>
                    </div>
                @empty
                    <p class="text-[#9FB3C8]">Aucun événement sécurité enregistré.</p>
                @endforelse
            </div>
        </x-dgcpt.card>
    </div>
</x-app-layout>
