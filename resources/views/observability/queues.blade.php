<x-dgcpt.card title="Queues" subtitle="Monitoring runtime">
    <div class="grid gap-4 md:grid-cols-4">
        <x-dgcpt.stat label="Ready" :value="$queueHealth['ready']" />
        <x-dgcpt.stat label="Draft" :value="$queueHealth['draft']" accent="#F4D000" />
        <x-dgcpt.stat label="Archived" :value="$queueHealth['archived']" accent="#FF5A5A" />
        <x-dgcpt.stat label="Throughput" :value="$queueHealth['throughput']" accent="#00A86B" />
    </div>
</x-dgcpt.card>
