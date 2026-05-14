<x-dgcpt.graph title="Tendances nationales" :value="$dashboardUx['charts']['maturity_score']" caption="Lecture synthétique des indicateurs IA-ready">
    <div class="grid gap-4 md:grid-cols-3">
        <x-dgcpt.progress label="Maturity score" :value="$dashboardUx['charts']['maturity_score']" :max="100" />
        <x-dgcpt.progress label="Compliance rate" :value="$dashboardUx['charts']['compliance_rate']" :max="100" />
        <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(255,255,255,0.02)] p-4">
            <p class="text-xs uppercase tracking-wide text-[#73D8FF]">Risk maturity</p>
            <p class="mt-2 text-lg font-bold text-[#E6EEF8]">{{ $dashboardUx['charts']['risk_maturity'] }}</p>
        </div>
    </div>
</x-dgcpt.graph>
