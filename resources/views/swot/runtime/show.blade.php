<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Strategic SWOT Runtime</p>
                <h1 class="dgcpt-page-title">{{ $mission->organisation }}</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">Analyse SWOT mission-level, recommandations et consolidation enterprise.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('swot.recommendations', $mission) }}" class="dgcpt-btn-outline">Recommendations</a>
                <a href="{{ route('swot.consolidation') }}" class="dgcpt-btn-outline">Consolidation</a>
            </div>
        </div>

        <div class="dgcpt-surface p-6 shadow-sm">
            <p class="dgcpt-card-title">Lancer une analyse</p>
            <form method="POST" action="{{ route('swot.analyze', $mission) }}" class="mt-4 grid gap-4 md:grid-cols-[1fr,1fr,auto]">
                @csrf
                <div>
                    <label class="dgcpt-label">Template SWOT</label>
                    <select name="swot_template_id" class="dgcpt-input">
                        @foreach ($swotTemplates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="dgcpt-label">Notes</label>
                    <input name="notes" type="text" class="dgcpt-input" placeholder="Contexte, observations, alignement..." />
                </div>
                <div class="flex items-end">
                    <button type="submit" class="dgcpt-btn-primary">Executer analyse</button>
                </div>
            </form>
        </div>

        <div class="dgcpt-surface p-6 shadow-sm">
            <p class="dgcpt-card-title">Analysis board</p>
            <h2 class="text-xl font-bold text-[#E6EEF8]">Score, priorites et timeline</h2>
            <div class="mt-5">
                @include('swot.runtime.analysis', ['swotView' => $swotView])
            </div>
        </div>

        <div class="dgcpt-surface p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="dgcpt-card-title">Recommendations</p>
                    <h2 class="text-xl font-bold text-[#E6EEF8]">Actions prioritaires</h2>
                </div>
            </div>
            <div class="mt-5">
                @include('swot.runtime.recommendations', ['swotView' => $swotView])
            </div>
        </div>
    </div>
</x-app-layout>
