<x-app-layout>
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

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Visual Workflow Runtime</p>
                <h1 class="dgcpt-page-title">{{ $mission->organisation }}</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">
                    {{ $runtime->instance->workflowTemplate?->name ?? 'Workflow système' }}
                    @if ($runtime->instance->currentStage)
                        · étape active : <span class="font-semibold text-[#73D8FF]">{{ $runtime->instance->currentStage->name }}</span>
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('workflow-runtime.dashboard') }}" class="dgcpt-btn-outline">Dashboard runtime</a>
                <a href="{{ route('workflow-runtime.observability') }}" class="dgcpt-btn-outline">Observability center</a>
                <a href="{{ route('missions.show', $mission) }}" class="dgcpt-btn-outline">Retour mission</a>
            </div>
        </div>

        @include('workflows.runtime.progress', ['runtime' => $runtime])

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

            <div class="space-y-6">
                <div class="dgcpt-surface p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="dgcpt-card-title">Stage Renderer UI</p>
                            <h2 class="text-xl font-bold text-[#E6EEF8]">Expérience stage courante</h2>
                        </div>
                    </div>

                    @if ($runtime->currentStageUi)
                        @include($runtime->currentStageUi['view'], ['ui' => $runtime->currentStageUi, 'runtime' => $runtime, 'mission' => $mission])
                    @else
                        <p class="mt-4 text-sm text-[#9FB3C8]">Aucune étape active à afficher.</p>
                    @endif
                </div>

                <div class="dgcpt-surface p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="dgcpt-card-title">Transitions</p>
                            <h2 class="text-xl font-bold text-[#E6EEF8]">Actions manuelles</h2>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-3">
                        @foreach ($runtime->availableTransitions as $transition)
                            <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] px-4 py-3 text-sm text-[#E6EEF8]">
                                {{ $transition->fromStage?->name ?? '—' }} → {{ $transition->toStage?->name ?? '—' }}
                                @if ($transition->is_automatic)
                                    <span class="ml-2 rounded-full bg-[rgba(0,168,107,0.12)] px-2 py-0.5 text-[11px] font-semibold text-[#7EF2BE]">Auto</span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 grid gap-3">
                        <form method="POST" action="{{ route('workflow-runtime.transition', $mission) }}" class="grid gap-3">
                            @csrf
                            <input type="hidden" name="action" value="reopen" />
                            <button type="submit" class="dgcpt-btn-outline">Rouvrir le workflow</button>
                        </form>

                        @if ($runtime->instance->currentStage)
                            <form method="POST" action="{{ route('workflow-runtime.transition', $mission) }}" class="grid gap-3">
                                @csrf
                                <input type="hidden" name="action" value="reject" />
                                <input type="hidden" name="stage_id" value="{{ $runtime->instance->currentStage->id }}" />
                                <textarea name="comment" rows="3" class="dgcpt-textarea" placeholder="Commentaire de rejet / blocage"></textarea>
                                <button type="submit" class="rounded-xl border border-[rgba(255,90,90,0.28)] px-4 py-2 text-sm font-semibold text-[#FFB4B4] hover:bg-[rgba(255,90,90,0.10)]">
                                    Marquer en échec
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                @include('workflows.runtime.execution-feed', ['runtime' => $runtime])
            </div>
        </div>
    </div>
</x-app-layout>
