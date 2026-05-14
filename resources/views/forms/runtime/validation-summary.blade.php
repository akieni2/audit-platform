<div class="rounded-2xl border {{ ($validationSummary['count'] ?? 0) > 0 ? 'border-[rgba(255,90,90,0.20)] bg-[rgba(58,26,32,0.45)]' : 'border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)]' }} p-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-[#E6EEF8]">Validation temps réel</p>
            <p class="mt-1 text-xs text-[#9FB3C8]">
                {{ ($validationSummary['count'] ?? 0) > 0 ? 'Des champs doivent être corrigés avant validation.' : 'Aucune erreur bloquante détectée dans la session courante.' }}
            </p>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ ($validationSummary['count'] ?? 0) > 0 ? 'bg-[rgba(255,90,90,0.12)] text-[#FFB4B4]' : 'bg-[rgba(0,168,107,0.12)] text-[#7EF2BE]' }}">
            {{ $validationSummary['count'] ?? 0 }} erreur(s)
        </span>
    </div>

    @if (($validationSummary['count'] ?? 0) > 0)
        <ul class="mt-3 list-disc space-y-1 pl-5 text-xs text-[#FFD4D4]">
            @foreach ($validationSummary['messages'] as $message)
                <li><span class="font-semibold">{{ $message['label'] }} :</span> {{ $message['message'] }}</li>
            @endforeach
        </ul>
    @endif
</div>
