<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-8 px-0 py-2">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Exécution SWOT</p>
                <h1 class="dgcpt-page-title">Recommandations SWOT</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">{{ $mission->organisation }}</p>
            </div>
            <a href="{{ route('swot.show', $mission) }}" class="dgcpt-btn-outline">Retour analyse</a>
        </div>

        @include('swot.runtime.recommendations', ['swotView' => $swotView])
    </div>
</x-app-layout>
