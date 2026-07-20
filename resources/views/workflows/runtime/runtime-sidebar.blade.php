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
                <p class="dgcpt-card-title">Exécution intelligente</p>
                <h2 class="text-xl font-bold text-[#E6EEF8]">Recommandations automatiques</h2>
            </div>
        </div>

        <div class="mt-4 grid gap-4">
            <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                <p class="text-sm font-semibold text-[#E6EEF8]">Score intelligent</p>
                <div class="mt-2 flex flex-wrap gap-3 text-xs text-[#BFD2E6]">
                    <span>Confiance: {{ data_get($runtimeRecommendations, 'intelligent_score.confidence', 0) }}%</span>
                    <span>Criticité suggérée : {{ \App\Support\UiLabel::translate(data_get($runtimeRecommendations, 'intelligent_score.suggested_criticality', 'medium')) }}</span>
                    <span>Méthodologie: {{ data_get($runtimeRecommendations, 'methodology.name', 'Aucune') }}</span>
                </div>
            </div>

            <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                <p class="text-sm font-semibold text-[#E6EEF8]">Contrôles suggérés</p>
                <div class="mt-3 space-y-2">
                    @forelse (data_get($runtimeRecommendations, 'risk_suggestions.recommendations', []) as $recommendation)
                        <div class="rounded-xl border border-[rgba(0,209,255,0.08)] px-3 py-2 text-xs text-[#BFD2E6]">
                            <span class="font-semibold text-[#E6EEF8]">{{ $recommendation['code'] }}</span>
                            — {{ $recommendation['title'] }}
                            @if (! empty($recommendation['library']))
                                <span class="text-[#73D8FF]">({{ $recommendation['library'] }})</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-[#9FB3C8]">Aucun contrôle suggéré pour ce contexte.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
                <p class="text-sm font-semibold text-[#E6EEF8]">Next best actions</p>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-xs text-[#BFD2E6]">
                    @foreach (data_get($runtimeRecommendations, 'next_actions', []) as $action)
                        <li>{{ $action }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
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
                    <input type="hidden" name="action" value="approve" />
                    <input type="hidden" name="stage_id" value="{{ $runtime->instance->currentStage->id }}" />
                    <textarea name="comment" rows="2" class="dgcpt-textarea" placeholder="Commentaire d'approbation"></textarea>
                    <button type="submit" class="dgcpt-btn-primary">Approuver visuellement</button>
                </form>

                <form method="POST" action="{{ route('workflow-runtime.transition', $mission) }}" class="grid gap-3">
                    @csrf
                    <input type="hidden" name="action" value="reject" />
                    <input type="hidden" name="stage_id" value="{{ $runtime->instance->currentStage->id }}" />
                    <textarea name="comment" rows="3" class="dgcpt-textarea" placeholder="Commentaire de rejet / blocage"></textarea>
                    <button type="submit" class="rounded-xl border border-[rgba(255,90,90,0.28)] px-4 py-2 text-sm font-semibold text-[#FFB4B4] hover:bg-[rgba(255,90,90,0.10)]">
                        Marquer en échec
                    </button>
                </form>

                <form method="POST" action="{{ route('workflow-runtime.transition', $mission) }}" class="grid gap-3">
                    @csrf
                    <input type="hidden" name="action" value="rollback" />
                    <select name="target_stage_id" class="dgcpt-input">
                        @foreach ($runtime->progress['items'] as $item)
                            <option value="{{ $item['stage']->id }}">{{ $item['stage']->name }}</option>
                        @endforeach
                    </select>
                    <textarea name="comment" rows="2" class="dgcpt-textarea" placeholder="Motif de rollback"></textarea>
                    <button type="submit" class="dgcpt-btn-outline">Rollback visuel</button>
                </form>
            @endif
        </div>
    </div>

    @include('workflows.runtime.runtime-feed', ['runtime' => $runtime])
</div>
