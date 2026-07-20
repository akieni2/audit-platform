<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-2">
            <p class="dgcpt-card-title">Exécution visuelle du workflow</p>
            <span class="rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $runtime->workflowState['badge_classes'] }}">
                {{ $runtime->workflowState['label'] }}
            </span>
        </div>
        <h1 class="dgcpt-page-title">{{ $mission->organisation }}</h1>
        <p class="mt-1 text-sm text-[#9FB3C8]">
            {{ $runtime->instance->workflowTemplate?->name ?? 'Workflow système' }}
            @if ($runtime->instance->currentStage)
                · étape active : <span class="font-semibold text-[#73D8FF]">{{ $runtime->instance->currentStage->name }}</span>
            @endif
        </p>
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('workflow-runtime.dashboard') }}" class="dgcpt-btn-outline">Tableau de bord d’exécution</a>
        <a href="{{ route('workflow-runtime.observability') }}" class="dgcpt-btn-outline">Observability center</a>
        <a href="{{ route('missions.show', $mission) }}" class="dgcpt-btn-outline">Retour mission</a>
    </div>
</div>
