<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-6 py-2">
        <div>
            <p class="dgcpt-card-title">{{ \App\Models\AdministrativeTask::priorityLabels()[$task->priority] }} — {{ \App\Models\AdministrativeTask::statusLabels()[$task->status] }}</p>
            <h1 class="dgcpt-page-title">{{ $task->title }}</h1>
            <p class="mt-1 text-sm dgcpt-text-muted">{{ $task->department?->name ?? 'Instruction transversale' }}</p>
        </div>
        @if (session('status'))<div class="dgcpt-surface border-[#00A86B]/35 p-4 text-sm">{{ session('status') }}</div>@endif
        <div class="grid gap-6 lg:grid-cols-[1.3fr_.7fr]">
            <section class="dgcpt-surface p-6">
                <h2 class="text-lg font-bold">Détails du traitement</h2>
                <p class="mt-4 whitespace-pre-line text-sm text-[#BFD2E6]">{{ $task->description ?: 'Aucune description.' }}</p>
                <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="dgcpt-card-title">Agent chargé</dt><dd>{{ $task->assignee?->displayName() ?? 'Non affecté' }}</dd></div>
                    <div><dt class="dgcpt-card-title">Responsable de validation</dt><dd>{{ $task->owner?->displayName() }}</dd></div>
                    <div><dt class="dgcpt-card-title">Échéance</dt><dd class="{{ $task->isOverdue() ? 'font-bold text-[#FF5A5A]' : '' }}">{{ $task->due_at?->format('d/m/Y H:i') ?? 'Non définie' }}</dd></div>
                    <div><dt class="dgcpt-card-title">Créée par</dt><dd>{{ $task->creator?->displayName() }}</dd></div>
                </dl>
                @if ($task->correspondenceRecord)
                    <a href="{{ route('correspondence.show', $task->correspondenceRecord) }}" class="mt-6 block rounded-xl border border-[rgba(0,209,255,.2)] p-4 text-sm text-[#73D8FF]">Courrier source : {{ $task->correspondenceRecord->reference }} — {{ $task->correspondenceRecord->subject }}</a>
                @endif
            </section>
            <form method="post" action="{{ route('administrative-work.status', $task) }}" class="dgcpt-surface h-fit space-y-4 p-5">
                @csrf @method('patch')
                <h2 class="font-bold">Faire évoluer le traitement</h2>
                <select name="status" class="dgcpt-select">@foreach (\App\Models\AdministrativeTask::statusLabels() as $value => $label)<option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>@endforeach</select>
                <button class="dgcpt-btn-primary" type="submit">Mettre à jour</button>
            </form>
        </div>
    </div>
</x-app-layout>
