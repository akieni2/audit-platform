<x-app-layout>
<form class="mx-auto max-w-4xl space-y-6 pb-10" method="post" action="{{ route('cfdt.submit', $course) }}">
    @csrf
    <header class="space-y-2">
        <p class="dgcpt-card-title">Évaluation CFDT</p>
        <h1 class="dgcpt-page-title">{{ $course->title }}</h1>
        <p class="dgcpt-text-muted">{{ count($course->questions ?? []) }} question(s) · seuil de réussite {{ $course->pass_mark }} % · sélectionnez votre réponse dans chaque carte.</p>
    </header>

    <div class="space-y-5">
        @foreach($course->questions ?? [] as $question)
        <section class="dgcpt-surface p-5 sm:p-6" aria-labelledby="question-{{ $question['id'] }}">
            <div class="mb-5 flex items-start gap-3">
                <span class="flex h-8 min-w-8 items-center justify-center rounded-full bg-cyan-500/15 text-sm font-black text-cyan-300">{{ $loop->iteration }}</span>
                <div class="min-w-0"><h2 id="question-{{ $question['id'] }}" class="break-words text-lg font-black leading-snug">{{ $question['text'] }}</h2><p class="mt-1 text-xs dgcpt-text-muted">{{ ($question['type'] ?? 'single') === 'multiple' ? 'Plusieurs réponses possibles' : 'Une seule réponse possible' }}</p></div>
            </div>
            <div class="space-y-3">
                @foreach($question['options'] as $optionIndex => $option)
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-700/80 px-4 py-3 transition hover:border-cyan-500/70 hover:bg-cyan-500/5 focus-within:border-cyan-400">
                    <input class="mt-1 h-5 w-5 shrink-0 accent-cyan-500" type="{{ ($question['type'] ?? 'single') === 'multiple' ? 'checkbox' : 'radio' }}" name="answers[{{ $question['id'] }}][]" value="{{ $optionIndex }}">
                    <span class="min-w-0 break-words leading-relaxed">{{ $option }}</span>
                </label>
                @endforeach
            </div>
        </section>
        @endforeach
    </div>

    <div class="sticky bottom-3 z-10 flex justify-end rounded-xl bg-slate-950/80 p-3 shadow-xl backdrop-blur sm:static sm:bg-transparent sm:p-0 sm:shadow-none">
        <button class="dgcpt-btn-primary w-full sm:w-auto">Soumettre définitivement</button>
    </div>
</form>
</x-app-layout>
