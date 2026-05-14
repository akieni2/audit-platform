<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <header class="space-y-2">
            <p class="dgcpt-card-title">Executive Analytics Platform</p>
            <h1 class="dgcpt-page-title">National Dashboard</h1>
            <p class="text-sm text-[#9FB3C8]">Vue consolidée nationale: gouvernance, intelligence risque, méthodologies, taxonomies et performance workflow.</p>
        </header>

        @include('executive.dashboard.kpi-cards', ['dashboardUx' => $dashboardUx])
        @include('executive.dashboard.analytics-grid', ['dashboardUx' => $dashboardUx])
    </div>
</x-app-layout>
