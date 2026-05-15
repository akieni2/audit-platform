<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-8 px-0 py-2">
        <div>
            <p class="dgcpt-card-title">Assistant mission</p>
            <h1 class="dgcpt-page-title">{{ $mission->organisation }}</h1>
            <p class="mt-1 text-sm text-[#9FB3C8]">IA assistive — l'humain garde le contrôle des validations.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr,260px]">
            <x-dgcpt.card title="Actions rapides" subtitle="Non contraignantes">
                <div class="flex flex-wrap gap-2">
                    <form method="post" action="{{ route('ai.audit.summary', $mission) }}">@csrf<button type="submit" class="dgcpt-btn-outline text-sm">Synthèse</button></form>
                    <form method="post" action="{{ route('ai.risk.analyze', $mission) }}">@csrf<button type="submit" class="dgcpt-btn-outline text-sm">Risques</button></form>
                    <form method="post" action="{{ route('ai.control.analyze', $mission) }}">@csrf<input type="hidden" name="framework" value="ISO27001"><button type="submit" class="dgcpt-btn-outline text-sm">Contrôles ISO</button></form>
                </div>
                <form method="post" action="{{ route('ai.ask', $mission) }}" class="mt-6 space-y-3">
                    @csrf
                    <textarea name="prompt" class="dgcpt-input w-full" rows="3" placeholder="Question contextuelle…" required></textarea>
                    <button type="submit" class="dgcpt-btn-primary">Demander au copilote</button>
                </form>
            </x-dgcpt.card>
            <x-dgcpt.ai-assistant-panel :mission="$mission" />
        </div>

        @if (count($history) > 0)
            <x-dgcpt.card title="Historique IA" subtitle="Recommandations passées">
                <div class="space-y-3">
                    @foreach ($history as $item)
                        <x-dgcpt.ai-recommendation-card :recommendation="$item" />
                    @endforeach
                </div>
            </x-dgcpt.card>
        @endif
    </div>
</x-app-layout>
