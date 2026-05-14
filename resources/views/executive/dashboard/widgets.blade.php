<div class="grid gap-4 md:grid-cols-3">
    @foreach ($dashboardUx['widgets'] as $widget)
        <x-dgcpt.graph :title="$widget['title']" :value="$widget['value']" :caption="$widget['caption']" />
    @endforeach
</div>
