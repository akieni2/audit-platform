<div class="space-y-4">
    <div class="rounded-2xl border border-[rgba(245,158,11,0.22)] bg-[rgba(33,24,8,0.72)] p-5">
        <p class="text-sm font-semibold text-[#E6EEF8]">{{ $ui['title'] }}</p>
        <p class="mt-2 text-sm text-[#FFD479]">Le stage heatmap s’appuie sur les projections consolidées existantes et les documents mission déjà produits.</p>
        <p class="mt-3 text-xs text-[#BFD2E6]">Documents liés: {{ $ui['metrics']['documents'] ?? 0 }}</p>
    </div>
    <a href="{{ $ui['action_url'] }}" class="dgcpt-btn-outline">Ouvrir le stage</a>
</div>
