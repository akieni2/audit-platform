@php
    /** @var \App\Models\QuestionnaireQuestion $question */
    /** @var \App\Models\QuestionnaireTemplate $template */
@endphp

<details class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4" {{ $loop->first ? 'open' : '' }}>
    <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-[#E6EEF8]">{{ $question->question }}</p>
            <p class="mt-1 text-xs font-mono text-[#7E92A7]">{{ $question->code ?: 'Sans code' }} · {{ $question->questionTypeLabel() }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($question->risk_level)
                <span class="rounded-full bg-[#402B09] px-2.5 py-1 text-xs font-semibold text-[#FFD479]">
                    {{ \App\Domain\Risk\Enums\CriticalityLevel::fromMixed($question->risk_level)?->label() ?? ucfirst($question->risk_level) }}
                </span>
            @endif
            @if ($question->allows_risk_detection)
                <span class="rounded-full bg-[#173050] px-2.5 py-1 text-xs font-semibold text-[#73D8FF]">Risk capture</span>
            @endif
            @if ($question->required)
                <span class="rounded-full bg-[#103824] px-2.5 py-1 text-xs font-semibold text-[#7EF2BE]">Obligatoire</span>
            @endif
        </div>
    </summary>

    <div class="mt-4 space-y-4 border-t border-[rgba(0,209,255,0.10)] pt-4">
        <div class="grid gap-4 md:grid-cols-[1fr,auto]">
            <div class="grid gap-2 text-sm text-[#9FB3C8] md:grid-cols-2">
                <p><span class="font-semibold text-[#E6EEF8]">Ordre :</span> {{ $question->sort_order }}</p>
                <p><span class="font-semibold text-[#E6EEF8]">Catégorie :</span> {{ $question->risk_category ?: '—' }}</p>
                <p><span class="font-semibold text-[#E6EEF8]">Observation :</span> {{ $question->allows_observation ? 'Oui' : 'Non' }}</p>
                <p><span class="font-semibold text-[#E6EEF8]">Active :</span> {{ $question->active ? 'Oui' : 'Non' }}</p>
            </div>
            <form method="POST" action="{{ route('questionnaire-builder.questions.destroy', $question) }}" onsubmit="return confirm('Archiver cette question ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg border border-[rgba(255,90,90,0.28)] px-3 py-2 text-xs font-semibold text-[#FF9B9B] hover:bg-[rgba(255,90,90,0.10)]">
                    Archiver
                </button>
            </form>
        </div>

        @if ($question->expected_documents)
            <div class="rounded-2xl bg-[rgba(8,24,48,0.55)] p-3 text-sm text-[#BFD2E6]">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Pièces attendues</p>
                <pre class="mt-2 whitespace-pre-wrap font-sans">{{ $question->expected_documents }}</pre>
            </div>
        @endif

        <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] p-4">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Modifier la question</p>
            @include('questionnaires.builder.partials.question-form', [
                'route' => route('questionnaire-builder.questions.update', $question),
                'method' => 'PATCH',
                'submitLabel' => 'Enregistrer la question',
                'question' => $question,
            ])
        </div>
    </div>
</details>
