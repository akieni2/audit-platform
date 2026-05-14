<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    @foreach ($dashboardUx['kpis'] as $card)
        <x-dgcpt.stat :label="$card['label']" :value="$card['value']" :accent="$card['accent']" />
    @endforeach
</div>
