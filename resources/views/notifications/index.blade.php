<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 py-10 space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Notifications</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Alertes workflow mission et décisions institutionnelles.</p>
            </div>
            @if (($unreadNotificationsCount ?? 0) > 0)
                <form method="post" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700">
                        Tout marquer comme lu
                    </button>
                </form>
            @endif
        </div>

        @if (session('status'))
            <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900 dark:border-green-800 dark:bg-green-900/20 dark:text-green-100">
                {{ session('status') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($notifications as $n)
                    @php
                        $data = $n->data;
                        $title = $data['title'] ?? ($data['body'] ?? 'Notification');
                        $body = $data['body'] ?? '';
                        $missionId = $data['mission_id'] ?? null;
                    @endphp
                    <li class="px-4 py-4 sm:px-6 {{ $n->read_at ? 'opacity-75' : 'bg-indigo-50/40 dark:bg-indigo-950/30' }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</p>
                                @if ($body)
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $body }}</p>
                                @endif
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $n->created_at?->translatedFormat('d M Y, H:i') }}
                                </p>
                                @if ($missionId)
                                    <p class="mt-2">
                                        <a href="{{ route('missions.show', $missionId) }}" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                                            Ouvrir la mission
                                        </a>
                                    </p>
                                @endif
                            </div>
                            @unless ($n->read_at)
                                <form method="post" action="{{ route('notifications.read', $n->id) }}">
                                    @csrf
                                    <button type="submit" class="rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200 hover:bg-indigo-50 dark:bg-gray-900 dark:text-indigo-300 dark:ring-indigo-800 dark:hover:bg-gray-800">
                                        Marquer lu
                                    </button>
                                </form>
                            @endunless
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                        Aucune notification pour le moment.
                    </li>
                @endforelse
            </ul>
        </div>

        @if ($notifications->hasPages())
            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
