<div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
    <p class="text-sm font-semibold text-[#E6EEF8]">Pièces jointes visuelles</p>
    <div class="mt-3 space-y-2">
        @forelse ($attachmentFields as $field)
            <div class="rounded-2xl border border-dashed border-[rgba(0,209,255,0.12)] px-4 py-3 text-xs text-[#BFD2E6]">
                <p class="font-semibold text-[#E6EEF8]">{{ $field['label'] }}</p>
                <p class="mt-1 text-[#9FB3C8]">{{ $field['help_text'] ?: 'Ajoutez un ou plusieurs fichiers pour cette étape.' }}</p>
            </div>
        @empty
            <p class="text-xs text-[#9FB3C8]">Aucun champ fichier dans ce formulaire.</p>
        @endforelse
    </div>
</div>
