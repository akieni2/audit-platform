<x-dgcpt.card title="Projections" subtitle="Santé des read models">
    <div class="grid gap-4 md:grid-cols-4">
        <x-dgcpt.stat label="OK" :value="$projectionHealth['ok']" accent="#00A86B" />
        <x-dgcpt.stat label="Warning" :value="$projectionHealth['warning']" accent="#F4D000" />
        <x-dgcpt.stat label="Failed" :value="$projectionHealth['failed']" accent="#FF5A5A" />
        <x-dgcpt.stat label="Dernier check" :value="$projectionHealth['latest'] ?: '—'" accent="#73D8FF" />
    </div>
</x-dgcpt.card>
