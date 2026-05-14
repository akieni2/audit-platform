<div class="space-y-4">
    <div class="rounded-2xl border border-[rgba(124,58,237,0.22)] bg-[rgba(20,11,39,0.72)] p-5">
        <p class="text-sm font-semibold text-[#E6EEF8]">{{ $ui['title'] }}</p>
        <p class="mt-2 text-sm text-[#C9AEFF]">Le stage risque alimente le registre et les workflows de revue/promotion sans casser les flux existants.</p>
        <p class="mt-3 text-xs text-[#BFD2E6]">Risques détectés: {{ $ui['metrics']['detected_risks'] ?? 0 }}</p>
    </div>
    <a href="{{ $ui['action_url'] }}" class="dgcpt-btn-primary">Ouvrir la capture de risque</a>
</div>
