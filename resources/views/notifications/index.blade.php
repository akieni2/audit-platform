<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-6 px-0 py-2">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-[0.65rem] font-bold uppercase tracking-[0.2em] text-dgcpt-cyan/90">Centre SOC</p>
                <h1 class="text-2xl font-extrabold uppercase tracking-wide text-slate-900 dark:text-white">Notifications</h1>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Alertes workflow mission, validations institutionnelles et décisions COPRI.</p>
            </div>
            @if (($unreadNotificationsCount ?? 0) > 0)
                <form method="post" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-dgcpt-cyan/40 bg-dgcpt-blue/40 px-4 py-2 text-sm font-bold uppercase tracking-widest text-dgcpt-cyan shadow-lg shadow-cyan-500/10 hover:bg-dgcpt-cyan/15">
                        Tout marquer comme lu
                    </button>
                </form>
            @endif
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-dgcpt-green/35 bg-dgcpt-green/10 px-4 py-3 text-sm font-medium text-emerald-100">
                {{ session('status') }}
            </div>
        @endif

        <div class="space-y-3">
            @forelse ($notifications as $n)
                @php
                    $data = $n->data;
                    $title = $data['title'] ?? ($data['body'] ?? 'Notification');
                    $body = $data['body'] ?? '';
                    $missionId = $data['mission_id'] ?? null;
                    $tone = 'info';
                    if (str_contains(strtolower($title), 'critique') || str_contains(strtolower($title), 'retard')) {
                        $tone = 'critical';
                    } elseif (str_contains(strtolower($title), 'risque') || str_contains(strtolower($title), 'rejet')) {
                        $tone = 'warning';
                    } elseif ($n->read_at) {
                        $tone = 'success';
                    }
                @endphp
                <x-ui.notification-card :title="$title" :time="$n->created_at?->translatedFormat('d M Y, H:i')" :tone="$tone" :read="(bool) $n->read_at">
                    @if ($body)
                        <p class="mt-1 text-sm text-slate-400">{{ $body }}</p>
                    @endif
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        @if ($missionId)
                            <a href="{{ route('missions.show', $missionId) }}" class="text-sm font-bold uppercase tracking-wider text-dgcpt-cyan hover:underline">
                                Ouvrir la mission
                            </a>
                        @endif
                        @unless ($n->read_at)
                            <form method="post" action="{{ route('notifications.read', $n->id) }}" class="inline">
                                @csrf
                                <button type="submit" class="rounded-lg border border-white/10 bg-white/5 px-3 py-1 text-xs font-bold uppercase tracking-widest text-slate-200 hover:bg-white/10">
                                    Marquer lu
                                </button>
                            </form>
                        @endunless
                    </div>
                </x-ui.notification-card>
            @empty
                <x-ui.dashboard-panel>
                    <p class="text-center text-sm text-slate-500 dark:text-slate-400">Aucune notification pour le moment.</p>
                </x-ui.dashboard-panel>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
