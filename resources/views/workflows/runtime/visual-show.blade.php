<div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
    @if (session('status'))
        <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="dgcpt-surface border-[rgba(255,90,90,0.30)] px-4 py-3 text-sm text-[#FFD4D4] ring-1 ring-[rgba(255,90,90,0.18)]">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('workflows.runtime.runtime-header', ['runtime' => $runtime, 'mission' => $mission])
    @include('workflows.runtime.stage-progress', ['runtime' => $runtime])

    <div class="grid gap-6 xl:grid-cols-[1.45fr,1fr]">
        <div class="space-y-6">
            @include('workflows.runtime.graph', ['runtime' => $runtime])

            <div class="dgcpt-surface p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="dgcpt-card-title">Execution stages</p>
                        <h2 class="text-xl font-bold text-[#E6EEF8]">Parcours des étapes</h2>
                    </div>
                </div>
                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($runtime->progress['items'] as $item)
                        @include('workflows.runtime.stage-card', ['item' => $item, 'runtime' => $runtime])
                    @endforeach
                </div>
            </div>

            @include('workflows.runtime.timeline', ['runtime' => $runtime])
        </div>

        @include('workflows.runtime.runtime-sidebar', [
            'runtime' => $runtime,
            'mission' => $mission,
            'runtimeRecommendations' => $runtimeRecommendations,
        ])
    </div>
</div>
