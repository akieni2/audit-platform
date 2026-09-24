<x-app-layout>
@php($course = $attempt->enrollment->course)
<div class="mx-auto max-w-4xl space-y-6 pb-10">
    <header class="dgcpt-surface p-6">
        <p class="dgcpt-card-title">Corrigé de l’évaluation</p><h1 class="dgcpt-page-title">{{ $course->title }}</h1>
        <div class="mt-5 grid gap-3 sm:grid-cols-3"><div><p class="dgcpt-text-muted">Résultat</p><p class="text-2xl font-black {{ $attempt->passed ? 'text-emerald-300' : 'text-amber-300' }}">{{ $attempt->passed ? 'Réussi' : 'Non réussi' }}</p></div><div><p class="dgcpt-text-muted">Score</p><p class="text-2xl font-black">{{ number_format($attempt->percentage, 1, ',') }} %</p></div><div><p class="dgcpt-text-muted">Points</p><p class="text-2xl font-black">{{ $attempt->score }}/{{ $attempt->total }}</p></div></div>
        <p class="mt-4 dgcpt-text-muted">Seuil requis : {{ $course->pass_mark }} % · soumis le {{ $attempt->submitted_at?->format('d/m/Y à H:i') }}</p>
    </header>

    @foreach($attempt->question_snapshot ?? [] as $question)
    @php($given = array_map('strval', (array) data_get($attempt->answers, $question['id'], [])))
    @php($correct = array_map('strval', (array) ($question['correct'] ?? [])))
    @php(sort($given)) @php(sort($correct)) @php($isCorrect = $given === $correct)
    <section class="dgcpt-surface border-l-4 p-5 sm:p-6 {{ $isCorrect ? 'border-l-emerald-500' : 'border-l-red-500' }}">
        <div class="flex items-start justify-between gap-3"><h2 class="text-lg font-black">{{ $loop->iteration }}. {{ $question['text'] }}</h2><span class="shrink-0 rounded-full px-3 py-1 text-xs font-black {{ $isCorrect ? 'bg-emerald-500/15 text-emerald-300' : 'bg-red-500/15 text-red-300' }}">{{ $isCorrect ? 'Correct' : 'Incorrect' }}</span></div>
        <div class="mt-4 space-y-2">@foreach($question['options'] ?? [] as $optionIndex => $option)@php($index=(string)$optionIndex)@php($selected=in_array($index,$given,true))@php($expected=in_array($index,$correct,true))<div class="flex items-start gap-3 rounded-xl border px-4 py-3 {{ $expected ? 'border-emerald-500/60 bg-emerald-500/5' : ($selected ? 'border-red-500/60 bg-red-500/5' : 'border-slate-800') }}"><span class="font-black">{{ $expected ? '✓' : ($selected ? '✕' : '○') }}</span><span>{{ $option }} @if($selected)<small class="ml-2">Votre réponse</small>@endif @if($expected)<small class="ml-2 text-emerald-300">Bonne réponse</small>@endif</span></div>@endforeach</div>
        @if(empty($given))<p class="mt-3 text-red-300">Aucune réponse fournie.</p>@endif
        @if(!empty($question['explanation']))<div class="mt-4 rounded-xl bg-cyan-500/5 p-4"><strong>Explication pédagogique :</strong> {{ $question['explanation'] }}</div>@endif
    </section>
    @endforeach

    <div class="flex flex-wrap gap-3"><a class="dgcpt-btn-secondary" href="{{ route('cfdt.index') }}">Retour à mon espace CFDT</a>@if(!$attempt->enrollment->isExpired() && $attempt->enrollment->attempts()->count() < 3)<a class="dgcpt-btn-primary" href="{{ route('cfdt.attempt',$course) }}">Refaire le test ({{ 3-$attempt->enrollment->attempts()->count() }} tentative(s) restante(s))</a>@endif @if($attempt->enrollment->certificate)<a class="dgcpt-btn-primary" href="{{ route('cfdt.certificate',$attempt->enrollment->certificate) }}">Télécharger le certificat</a>@endif</div>
</div>
</x-app-layout>
