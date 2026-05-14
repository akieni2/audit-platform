<div class="grid gap-6 xl:grid-cols-[1.2fr,0.8fr]">
    <div class="space-y-6">
        @include('executive.dashboard.widgets', ['dashboardUx' => $dashboardUx])
        @include('executive.dashboard.charts', ['dashboardUx' => $dashboardUx])
    </div>

    <div class="space-y-6">
        @include('executive.dashboard.alerts', ['dashboardUx' => $dashboardUx])
        @include('executive.dashboard.live-feed', ['dashboardUx' => $dashboardUx])
    </div>
</div>
