@php
    /** @var \App\Models\QuestionnaireSection $section */
    /** @var \App\Models\QuestionnaireTemplate $template */
@endphp

<details class="dgcpt-surface border-[rgba(0,209,255,0.12)] p-6 shadow-sm ring-1 ring-[rgba(0,209,255,0.08)]" {{ $loop->first ? 'open' : '' }}>
    <summary class="flex cursor-pointer list-none flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-[#173050] px-2.5 py-1 text-xs font-semibold text-[#73D8FF]">{{ $section->typeLabel() }}</span>
                <span class="rounded-full bg-[rgba(0,168,107,0.12)] px-2.5 py-1 text-xs font-semibold text-[#7EF2BE]">
                    {{ $section->questions->count() }} question(s)
                </span>
            </div>
            <h2 class="mt-3 text-lg font-bold text-[#E6EEF8]">{{ $section->title }}</h2>
            @if ($section->parent)
                <p class="mt-1 text-xs text-[#73D8FF]">Sous : {{ $section->parent->title }}</p>
            @endif
            @if ($section->description)
                <p class="mt-1 text-sm text-[#9FB3C8]">{{ $section->description }}</p>
            @endif
        </div>
        <div class="text-sm text-[#9FB3C8]">Ordre {{ $section->sort_order }}</div>
    </summary>

    <div class="mt-5 space-y-5 border-t border-[rgba(0,209,255,0.12)] pt-5">
        <div class="grid gap-4 lg:grid-cols-[1.3fr,auto]">
            <form method="POST" action="{{ route('questionnaire-builder.sections.update', $section) }}" class="grid gap-3 md:grid-cols-3">
                @csrf
                @method('PATCH')
                <div class="md:col-span-2">
                    <label class="dgcpt-label">Titre</label>
                    <input name="title" type="text" value="{{ $section->title }}" required class="dgcpt-input" />
                </div>
                <div>
                    <label class="dgcpt-label">Ordre</label>
                    <input name="sort_order" type="number" min="0" value="{{ $section->sort_order }}" class="dgcpt-input" />
                </div>
                <div class="md:col-span-3">
                    <label class="dgcpt-label">Description</label>
                    <textarea name="description" rows="2" class="dgcpt-textarea">{{ $section->description }}</textarea>
                </div>
                <div>
                    <label class="dgcpt-label">Niveau</label>
                    <select name="section_type" required class="dgcpt-select">
                        @foreach (\App\Models\QuestionnaireSection::typeLabels() as $type => $label)
                            <option value="{{ $type }}" @selected($section->section_type === $type)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="dgcpt-label">Structure parente</label>
                    <select name="parent_section_id" class="dgcpt-select">
                        <option value="">Aucune</option>
                        @foreach ($template->sections->where('id', '!=', $section->id)->sortBy('sort_order') as $parentOption)
                            <option value="{{ $parentOption->id }}" @selected((int) $section->parent_section_id === (int) $parentOption->id)>{{ $parentOption->typeLabel() }} — {{ $parentOption->title }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="dgcpt-btn-outline w-fit">Mettre à jour l’élément</button>
            </form>

            <form method="POST" action="{{ route('questionnaire-builder.sections.destroy', $section) }}" onsubmit="return confirm('Archiver cette section et ses questions ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg border border-[rgba(255,90,90,0.28)] px-3 py-2 text-xs font-semibold text-[#FF9B9B] hover:bg-[rgba(255,90,90,0.10)]">
                    Archiver la section
                </button>
            </form>
        </div>

        @if ($section->questions->isNotEmpty())
            <form method="POST" action="{{ route('questionnaire-builder.questions.reorder') }}" class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(7,16,34,0.65)] p-4">
                @csrf
                <input type="hidden" name="section_id" value="{{ $section->id }}">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Réordonner les questions</p>
                        <p class="mt-1 text-sm text-[#9FB3C8]">Renseignez l’ordre cible puis appliquez.</p>
                    </div>
                    <button type="submit" class="dgcpt-btn-outline">Appliquer</button>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach ($section->questions as $question)
                        <label class="rounded-xl border border-[rgba(0,209,255,0.08)] bg-[rgba(5,8,22,0.7)] p-3">
                            <span class="block text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">{{ $question->code ?: 'Sans code' }}</span>
                            <span class="mt-1 block text-sm text-[#E6EEF8]">{{ \Illuminate\Support\Str::limit($question->question, 90) }}</span>
                            <input name="positions[{{ $question->id }}]" type="number" min="0" value="{{ $question->sort_order }}" class="dgcpt-input mt-3" />
                        </label>
                    @endforeach
                </div>
            </form>
        @endif

        <div class="space-y-4">
            @foreach ($section->questions->sortBy('sort_order') as $question)
                @include('questionnaires.builder.partials.question-card', ['question' => $question, 'template' => $template])
            @endforeach
        </div>

        <div class="rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(7,16,34,0.55)] p-5">
            <p class="mb-4 text-xs font-semibold uppercase tracking-wide text-[#73D8FF]">Ajouter une question</p>
            @include('questionnaires.builder.partials.question-form', [
                'route' => route('questionnaire-builder.questions.store', $section),
                'submitLabel' => 'Ajouter la question',
                'question' => null,
            ])
        </div>
    </div>
</details>
