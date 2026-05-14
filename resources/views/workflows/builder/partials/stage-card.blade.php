@php
    $stageType = $stage->resolvedStageType();
    $executionMode = $stage->resolvedExecutionMode();
    $isSelected = isset($selectedStage) && $selectedStage?->is($stage);
@endphp

<div
    class="workflow-stage-card absolute w-60 rounded-2xl border bg-[#07111F] p-4 shadow-lg transition"
    data-stage-id="{{ $stage->id }}"
    draggable="true"
    style="left: {{ (int) ($stage->position_x ?? 0) }}px; top: {{ (int) ($stage->position_y ?? 0) }}px; border-color: {{ $stage->color ?: 'rgba(0,209,255,0.18)' }};"
>
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-[#E6EEF8]">{{ $stage->name }}</p>
            <p class="mt-1 text-[11px] font-mono uppercase tracking-wide text-[#7E92A7]">{{ $stage->code }}</p>
        </div>
        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $isSelected ? 'bg-[#173050] text-[#73D8FF]' : 'bg-[#17223B] text-[#BFD2E6]' }}">
            {{ $stageType?->label() ?? 'Stage' }}
        </span>
    </div>

    <div class="mt-3 space-y-2 text-xs text-[#9FB3C8]">
        <p><span class="font-semibold text-[#E6EEF8]">Exécution :</span> {{ $executionMode?->label() ?? '—' }}</p>
        <p><span class="font-semibold text-[#E6EEF8]">Questionnaire :</span> {{ $stage->questionnaireTemplate?->name ?? '—' }}</p>
        <p><span class="font-semibold text-[#E6EEF8]">Coordonnées :</span> <span data-position-label>{{ (int) ($stage->position_x ?? 0) }}, {{ (int) ($stage->position_y ?? 0) }}</span></p>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        <a href="{{ route('workflow-builder.edit', ['template' => $template, 'stage' => $stage->id]) }}"
           class="rounded-lg border border-[rgba(0,209,255,0.18)] px-3 py-1.5 text-xs font-semibold text-[#73D8FF] hover:bg-[rgba(0,209,255,0.08)]">
            Configurer
        </a>
        @if ($isSelected)
            <span class="rounded-lg bg-[rgba(0,168,107,0.12)] px-3 py-1.5 text-xs font-semibold text-[#7EF2BE]">Sélectionnée</span>
        @endif
    </div>
</div>
