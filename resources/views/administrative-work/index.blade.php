<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 py-2">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Pilotage administratif</p>
                <h1 class="dgcpt-page-title">Instructions et tâches</h1>
                <p class="mt-1 text-sm dgcpt-text-muted">Responsabilités, échéances, soumissions et validations administratives.</p>
            </div>
            <a href="{{ route('administrative-work.create') }}" class="dgcpt-btn-primary">Nouvelle instruction</a>
        </div>
        @if (session('status'))<div class="dgcpt-surface border-[#00A86B]/35 p-4 text-sm">{{ session('status') }}</div>@endif
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['Tâches actives', $stats['active']], ['Mes tâches', $stats['mine']], ['En retard', $stats['overdue']], ['Validées', $stats['validated']]] as [$label, $value])
                <div class="dgcpt-surface p-5"><p class="dgcpt-card-title">{{ $label }}</p><p class="mt-2 text-3xl font-black text-[#00D1FF]">{{ $value }}</p></div>
            @endforeach
        </div>
        <form method="get" class="dgcpt-filter-bar">
            <div class="w-full sm:w-64"><label class="dgcpt-label">Statut</label><select name="status" class="dgcpt-select"><option value="">Tous</option>@foreach (\App\Models\AdministrativeTask::statusLabels() as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
            <button class="dgcpt-btn-primary" type="submit">Filtrer</button>
        </form>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($tasks as $task)
                <article class="dgcpt-surface p-5">
                    <div class="flex justify-between gap-3"><span class="dgcpt-card-title">{{ \App\Models\AdministrativeTask::priorityLabels()[$task->priority] }}</span><span class="text-xs {{ $task->isOverdue() ? 'font-bold text-[#FF5A5A]' : 'text-[#9FB3C8]' }}">{{ $task->due_at?->format('d/m/Y H:i') ?? 'Sans échéance' }}</span></div>
                    <h2 class="mt-3 text-lg font-bold"><a class="hover:text-[#00D1FF]" href="{{ route('administrative-work.show', $task) }}">{{ $task->title }}</a></h2>
                    <p class="mt-2 text-sm text-[#BFD2E6]">{{ \Illuminate\Support\Str::limit($task->description, 140) }}</p>
                    <div class="mt-4 border-t border-[rgba(0,209,255,.12)] pt-3 text-xs text-[#9FB3C8]">
                        <p>{{ $task->department?->name ?? 'Transversal' }}</p>
                        <p class="mt-1">Responsable : {{ $task->assignee?->displayName() ?? 'Non affecté' }}</p>
                        <p class="mt-1 text-[#73D8FF]">{{ \App\Models\AdministrativeTask::statusLabels()[$task->status] }}</p>
                    </div>
                </article>
            @empty
                <div class="dgcpt-surface p-8 text-center text-[#9FB3C8] md:col-span-2 xl:col-span-3">Aucune tâche administrative visible.</div>
            @endforelse
        </div>
        {{ $tasks->links() }}
    </div>
</x-app-layout>
