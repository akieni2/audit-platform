<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-8 px-0 py-2">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">AI Copilot</p>
                <h1 class="dgcpt-page-title">Audit & Risk Copilot</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">
                    Assistance IA — driver <span class="font-mono text-[#00D1FF]">{{ $driver }}</span>.
                    Aucune décision automatique.
                </p>
            </div>
            <a href="{{ route('ai.recommendations') }}" class="dgcpt-btn-outline">Recommandations</a>
        </div>

        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8]">{{ session('status') }}</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1fr,280px]">
            <x-dgcpt.card title="Assistant" subtitle="Suggestions contextuelles">
                @isset($mission)
                    <form method="post" action="{{ route('ai.ask', $mission) }}" class="space-y-4">
                        @csrf
                        <textarea name="prompt" rows="4" class="dgcpt-input w-full" placeholder="Posez une question sur la mission…" required></textarea>
                        <button type="submit" class="dgcpt-btn-primary">Obtenir une suggestion IA</button>
                    </form>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <form method="post" action="{{ route('ai.audit.summary', $mission) }}">@csrf<button type="submit" class="dgcpt-btn-outline text-sm">Synthèse audit</button></form>
                        <form method="post" action="{{ route('ai.risk.analyze', $mission) }}">@csrf<button type="submit" class="dgcpt-btn-outline text-sm">Analyse risques</button></form>
                    </div>
                @else
                    <p class="text-sm text-[#9FB3C8]">Sélectionnez une mission pour activer le copilote contextuel.</p>
                @endisset
            </x-dgcpt.card>
            <x-dgcpt.ai-assistant-panel :mission="$mission ?? null" />
        </div>
    </div>
</x-app-layout>

