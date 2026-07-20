<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Observability Center</p>
                <h1 class="dgcpt-page-title">Centre d’observabilité des workflows</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">Supervision de l’exécution, santé des projections, files logiques, mesures et erreurs consolidées.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('workflow-runtime.dashboard') }}" class="dgcpt-btn-outline">Tableau de bord d’exécution</a>
                <a href="{{ route('observability.enterprise.health') }}" class="dgcpt-btn-outline">Enterprise health</a>
                <a href="{{ route('observability.enterprise.queues') }}" class="dgcpt-btn-outline">Queues</a>
                <a href="{{ route('observability.enterprise.security') }}" class="dgcpt-btn-outline">Security</a>
                <a href="{{ route('dashboard') }}" class="dgcpt-btn-outline">Retour au tableau de bord</a>
            </div>
        </div>

        @include('observability.runtime-health', ['runtimeHealth' => $runtimeHealth])

        <div class="grid gap-6 xl:grid-cols-2">
            @include('observability.queues', ['queueHealth' => $queueHealth])
            @include('observability.projections', ['projectionHealth' => $projectionHealth])
        </div>

        <div class="grid gap-6 xl:grid-cols-[0.9fr,1.1fr]">
            @include('observability.errors', ['errorSummary' => $errorSummary])
            @include('observability.metrics', ['runtimeMetrics' => $runtimeMetrics, 'businessEvents' => $businessEvents])
        </div>

        @include('observability.swot-raci-audit', ['swotAuditLogs' => $swotAuditLogs, 'raciAuditLogs' => $raciAuditLogs])
    </div>
</x-app-layout>
