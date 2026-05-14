@php
    $stage = $item['stage'];
    $execution = $item['execution'];
    $visual = $item['visual'];
@endphp

<div class="rounded-2xl border p-4 {{ $visual['card_classes'] }}">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-[#E6EEF8]">{{ $stage->name }}</p>
            <p class="mt-1 text-[11px] font-mono uppercase tracking-wide text-[#9FB3C8]">{{ $stage->code }}</p>
        </div>
        <span class="rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $visual['badge_classes'] }}">
            {{ $visual['label'] }}
        </span>
    </div>

    <div class="mt-4 space-y-2 text-xs text-[#BFD2E6]">
        <p><span class="font-semibold text-[#E6EEF8]">Composant :</span> {{ $stage->resolvedComponentKey() }}</p>
        <p><span class="font-semibold text-[#E6EEF8]">Exécution :</span> {{ $stage->resolvedExecutionMode()?->label() ?? '—' }}</p>
        @if ($execution?->assignee)
            <p><span class="font-semibold text-[#E6EEF8]">Assigné à :</span> {{ $execution->assignee->displayName() }}</p>
        @endif
        @if ($execution?->started_at)
            <p><span class="font-semibold text-[#E6EEF8]">Début :</span> {{ $execution->started_at->format('d/m/Y H:i') }}</p>
        @endif
        @if ($execution?->completed_at)
            <p><span class="font-semibold text-[#E6EEF8]">Fin :</span> {{ $execution->completed_at->format('d/m/Y H:i') }}</p>
        @endif
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        @if ($item['is_current'])
            <a href="{{ route('workflow-runtime.stage', ['mission' => $runtime->instance->mission_id, 'stage' => $stage]) }}" class="dgcpt-btn-outline">
                Ouvrir
            </a>
        @endif
        @if ($item['is_current'] && $stage->allow_skip)
            <form method="POST" action="{{ route('workflow-runtime.transition', $runtime->instance->mission_id) }}">
                @csrf
                <input type="hidden" name="action" value="skip" />
                <input type="hidden" name="stage_id" value="{{ $stage->id }}" />
                <button type="submit" class="rounded-lg border border-[rgba(245,158,11,0.24)] px-3 py-1.5 text-xs font-semibold text-[#FFD479] hover:bg-[rgba(245,158,11,0.12)]">
                    Skip
                </button>
            </form>
        @endif
        @if (($item['visual_state']->value ?? null) === 'failed')
            <form method="POST" action="{{ route('workflow-runtime.transition', $runtime->instance->mission_id) }}">
                @csrf
                <input type="hidden" name="action" value="retry" />
                <input type="hidden" name="stage_id" value="{{ $stage->id }}" />
                <button type="submit" class="rounded-lg border border-[rgba(255,90,90,0.24)] px-3 py-1.5 text-xs font-semibold text-[#FFB4B4] hover:bg-[rgba(255,90,90,0.12)]">
                    Relancer
                </button>
            </form>
        @endif
    </div>
</div>
