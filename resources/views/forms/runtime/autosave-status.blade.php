<div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] px-4 py-3 text-sm text-[#BFD2E6]">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="font-semibold text-[#E6EEF8]">{{ $autosaveData['label'] ?? 'Autosave prêt' }}</p>
            <p class="mt-1 text-xs text-[#9FB3C8]">
                @if (! empty($autosaveData['last_saved_at']))
                    Dernière sauvegarde : {{ $autosaveData['last_saved_at'] }}
                @else
                    Le brouillon est sauvegardé automatiquement pendant la saisie.
                @endif
            </p>
        </div>
        <span class="rounded-full bg-[rgba(0,168,107,0.12)] px-3 py-1 text-xs font-semibold text-[#7EF2BE]" data-autosave-indicator>
            {{ strtoupper($autosaveData['status'] ?? 'draft') }}
        </span>
    </div>
</div>
