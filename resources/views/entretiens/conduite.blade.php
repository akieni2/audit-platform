<x-app-layout>
    @php
        $criticalityOptions = \App\Domain\Risk\Enums\CriticalityLevel::options();

        /** @var \App\Models\Entretien $entretien */
        /** @var \App\Models\QuestionnaireTemplate $template */
        /** @var \Illuminate\Support\Collection<int, \App\Models\EntretienResponse> $existingResponses */
    @endphp

    <div class="mx-auto max-w-5xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Conduite d’entretien</p>

                <h1 class="dgcpt-page-title">
                    {{ $template->name }}
                </h1>

                <p class="text-sm text-[#9FB3C8]">
                    Mission
                    <span class="font-mono text-[#00D1FF]">
                        {{ $entretien->mission?->reference ?: '—' }}
                    </span>

                    · Service
                    <span class="font-semibold text-[#E6EEF8]">
                        {{ $entretien->service?->nom ?? '—' }}
                    </span>
                </p>
            </div>

            <a
                href="{{ route('entretiens.index', $entretien->service_id) }}"
                class="dgcpt-btn-outline"
            >
                ? Entretiens du service
            </a>
        </div>

        @if ($progressPercent !== null)
            <div class="dgcpt-surface border-[rgba(0,209,255,0.15)] p-4 shadow-sm ring-1 ring-[rgba(0,209,255,0.12)]">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-[#E6EEF8]">
                        Progression questionnaire
                    </p>

                    <span class="font-mono text-sm text-[#00D1FF]">
                        {{ $progressPercent }}%
                    </span>
                </div>

                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-[#0A1530]">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-[#00A86B] to-[#00D1FF]"
                        style="width: {{ $progressPercent }}%"
                    ></div>
                </div>

                <p class="mt-2 text-xs text-[#9FB3C8]">
                    Statut entretien :
                    <span class="font-semibold text-[#E6EEF8]">
                        {{ \App\Models\Entretien::statusLabels()[$entretien->status] ?? $entretien->status }}
                    </span>

                    — sauvegarde progressive : enregistrez autant de fois que nécessaire.
                </p>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('entretiens.dynamic-responses.store', $entretien) }}"
            class="space-y-8"
        >
            @csrf

            @php $responseIndex = 0; @endphp

            @foreach ($template->sections as $section)
                <div class="dgcpt-surface border-[rgba(0,209,255,0.12)] p-6 shadow-sm ring-1 ring-[rgba(0,209,255,0.08)]">

                    <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">
                        {{ $section->title }}
                    </h2>

                    @if ($section->description)
                        <p class="mt-2 text-sm text-[#9FB3C8]">
                            {{ $section->description }}
                        </p>
                    @endif

                    <div class="mt-6 space-y-8">

                        @foreach ($section->questions as $question)

                            @php
                                $ex = $existingResponses->get($question->id);

                                $meta = $question->metadata ?? [];
                                $opts = $meta['options'] ?? [];

                                if (! is_array($opts)) {
                                    $opts = [];
                                }

                                $namePrefix = "responses[{$responseIndex}]";
                                $oldKey = "responses.{$responseIndex}";
                            @endphp

                            <div class="border-t border-[rgba(0,209,255,0.1)] pt-6 first:border-t-0 first:pt-0">

                                <input
                                    type="hidden"
                                    name="{{ $namePrefix }}[questionnaire_question_id]"
                                    value="{{ $question->id }}"
                                />

                                <div class="flex flex-wrap items-start justify-between gap-2">

                                    <div>

                                        @if ($question->code)
                                            <p class="font-mono text-xs text-[#00D1FF]">
                                                {{ $question->code }}
                                            </p>
                                        @endif

                                        <p class="font-semibold text-[#E6EEF8]">
                                            {{ $question->question }}

                                            @if ($question->required)
                                                <span class="text-[#FF5A5A]">*</span>
                                            @endif
                                        </p>

                                        @if ($question->help_text)
                                            <p class="mt-1 text-sm text-[#9FB3C8]">
                                                {{ $question->help_text }}
                                            </p>
                                        @endif

                                    </div>

                                    <span class="rounded border border-[rgba(0,209,255,0.25)] px-2 py-0.5 font-mono text-xs text-[#9FB3C8]">
                                        {{ $question->question_type }}
                                    </span>

                                </div>

                                <div class="mt-4 space-y-4">

                                    @switch($question->question_type)

                                        @case(\App\Models\QuestionnaireQuestion::TYPE_BOOLEAN_NA)

                                            @php
                                                $tri = old(
                                                    $oldKey.'.answer_tri',
                                                    $ex?->answer_boolean === true
                                                        ? 'yes'
                                                        : ($ex?->answer_boolean === false ? 'no' : 'na')
                                                );
                                            @endphp

                                            <div class="flex flex-wrap gap-4 text-sm text-[#E6EEF8]">

                                                <label class="inline-flex items-center gap-2">
                                                    <input
                                                        type="radio"
                                                        name="{{ $namePrefix }}[answer_tri]"
                                                        value="yes"
                                                        class="rounded border-[rgba(0,209,255,0.35)]"
                                                        @checked($tri === 'yes')
                                                    />
                                                    Oui
                                                </label>

                                                <label class="inline-flex items-center gap-2">
                                                    <input
                                                        type="radio"
                                                        name="{{ $namePrefix }}[answer_tri]"
                                                        value="no"
                                                        class="rounded border-[rgba(0,209,255,0.35)]"
                                                        @checked($tri === 'no')
                                                    />
                                                    Non
                                                </label>

                                                <label class="inline-flex items-center gap-2">
                                                    <input
                                                        type="radio"
                                                        name="{{ $namePrefix }}[answer_tri]"
                                                        value="na"
                                                        class="rounded border-[rgba(0,209,255,0.35)]"
                                                        @checked($tri === 'na')
                                                    />
                                                    N/A
                                                </label>

                                            </div>

                                            @break

                                        @case(\App\Models\QuestionnaireQuestion::TYPE_TEXTAREA)

                                            <textarea
                                                name="{{ $namePrefix }}[answer_text]"
                                                rows="3"
                                                class="dgcpt-textarea w-full"
                                            >{{ old($oldKey.'.answer_text', $ex?->answer_text) }}</textarea>

                                            @break

                                        @default

                                            <textarea
                                                name="{{ $namePrefix }}[answer_text]"
                                                rows="2"
                                                class="dgcpt-textarea w-full"
                                                placeholder="Réponse"
                                            >{{ old($oldKey.'.answer_text', $ex?->answer_text) }}</textarea>

                                    @endswitch

                                    @if ($question->allows_observation)

                                        <div>
                                            <label class="dgcpt-label">
                                                Observation / anomalies
                                            </label>

                                            <textarea
                                                name="{{ $namePrefix }}[observation]"
                                                rows="2"
                                                class="dgcpt-textarea w-full"
                                            >{{ old($oldKey.'.observation', $ex?->observation) }}</textarea>
                                        </div>

                                    @endif

                                    @if ($question->expected_documents)

                                        <p class="text-xs text-[#9FB3C8]">
                                            <span class="font-semibold text-[#E6EEF8]">
                                                Pièces attendues :
                                            </span>

                                            {{ $question->expected_documents }}
                                        </p>

                                        <p class="text-xs italic text-[#6B7F95]">
                                            L’envoi de fichiers sera branché sur mission_documents
                                            (phase ultérieure).
                                        </p>

                                    @endif

                                </div>

                            </div>

                            @php $responseIndex++; @endphp

                        @endforeach

                    </div>

                </div>
            @endforeach

            <div class="flex flex-wrap gap-3">

                <button type="submit" class="dgcpt-btn-primary">
                    Enregistrer les réponses
                </button>

                <a
                    href="{{ route('entretiens.index', $entretien->service_id) }}"
                    class="dgcpt-btn-outline"
                >
                    Annuler
                </a>

            </div>

        </form>
    </div>
</x-app-layout>