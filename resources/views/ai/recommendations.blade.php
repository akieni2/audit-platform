<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-8 px-0 py-2">
        <div>
            <p class="dgcpt-card-title">Recommandations IA</p>
            <h1 class="dgcpt-page-title">Suggestions assistives</h1>
            <p class="mt-1 text-sm text-[#9FB3C8]">Toutes les recommandations requièrent une validation humaine.</p>
        </div>

        <div class="space-y-4">
            @forelse ($recommendations as $recommendation)
                <x-dgcpt.ai-recommendation-card :recommendation="$recommendation" />
                @if ($recommendation->requires_human_validation && $recommendation->accepted === null && $recommendation->mission)
                    <form method="post" action="{{ route('ai.recommendations.accept', $recommendation) }}" class="flex gap-2 pl-1">
                        @csrf
                        <input type="hidden" name="accepted" value="1">
                        <button type="submit" class="dgcpt-btn-outline text-xs">Marquer revue (humain)</button>
                    </form>
                @endif
            @empty
                <p class="text-sm text-[#9FB3C8]">Aucune recommandation IA pour le moment.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
