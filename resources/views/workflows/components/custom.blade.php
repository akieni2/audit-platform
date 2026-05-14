<div class="space-y-4">
    <div class="rounded-2xl border border-[rgba(148,163,184,0.18)] bg-[rgba(12,18,26,0.72)] p-5">
        <p class="text-sm font-semibold text-[#E6EEF8]">{{ $ui['title'] }}</p>
        <p class="mt-2 text-sm text-[#BFD2E6]">{{ $ui['description'] ?: 'Stage personnalisé prêt à être orchestré par le moteur runtime.' }}</p>
    </div>
    <a href="{{ $ui['action_url'] }}" class="dgcpt-btn-outline">Ouvrir le stage</a>
</div>
