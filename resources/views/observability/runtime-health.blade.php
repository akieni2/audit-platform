<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
    <x-dgcpt.stat label="Business events" :value="$runtimeHealth['events']" />
    <x-dgcpt.stat label="Runtime metrics" :value="$runtimeHealth['metrics']" accent="#00A86B" />
    <x-dgcpt.stat label="Templates" :value="$runtimeHealth['templates']" accent="#F4D000" />
    <x-dgcpt.stat label="Actifs" :value="$runtimeHealth['active_templates']" accent="#D8B4FE" />
    <x-dgcpt.stat label="Dernier event" :value="$runtimeHealth['latest_event_at'] ?: '—'" accent="#00D1FF" />
</div>
