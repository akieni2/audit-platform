<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-6 py-2">
        <div><p class="dgcpt-card-title">Traitement administratif</p><h1 class="dgcpt-page-title">Nouvelle instruction</h1><p class="mt-1 text-sm dgcpt-text-muted">Créez une action administrative avec un responsable et une échéance.</p></div>
        <form method="post" action="{{ route('administrative-work.store') }}" class="dgcpt-surface space-y-5 p-6">
            @csrf
            @if ($errors->any())<div class="rounded-lg bg-red-950/40 p-3 text-sm text-red-300">{{ $errors->first() }}</div>@endif
            @if ($correspondence)
                <input type="hidden" name="correspondence_record_id" value="{{ $correspondence->id }}">
                <div class="rounded-xl border border-[rgba(0,209,255,.2)] p-4 text-sm"><span class="font-mono text-[#73D8FF]">{{ $correspondence->reference }}</span><strong class="ml-2">{{ $correspondence->subject }}</strong></div>
            @endif
            <div><label class="dgcpt-label">Instruction ou tâche</label><input name="title" value="{{ old('title', $correspondence ? 'Traiter : '.$correspondence->subject : '') }}" class="dgcpt-input" required></div>
            <div><label class="dgcpt-label">Description et résultat attendu</label><textarea name="description" rows="5" class="dgcpt-textarea">{{ old('description') }}</textarea></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="dgcpt-label">Priorité</label><select name="priority" class="dgcpt-select">@foreach (\App\Models\AdministrativeTask::priorityLabels() as $value => $label)<option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div><label class="dgcpt-label">Échéance</label><input type="datetime-local" name="due_at" value="{{ old('due_at') }}" class="dgcpt-input"></div>
                <div><label class="dgcpt-label">Direction ou service</label><select name="department_id" class="dgcpt-select"><option value="">Transversal</option>@foreach ($departments as $department)<option value="{{ $department->id }}">{{ $department->code }} — {{ $department->name }}</option>@endforeach</select></div>
                <div><label class="dgcpt-label">Agent chargé du traitement</label><select name="assignee_id" class="dgcpt-select"><option value="">À affecter ultérieurement</option>@foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->displayName() }} — {{ $user->department?->code }}</option>@endforeach</select></div>
                <div class="sm:col-span-2"><label class="dgcpt-label">Responsable de validation</label><select name="owner_id" class="dgcpt-select" required>@foreach ($users as $user)<option value="{{ $user->id }}" @selected((int) old('owner_id', auth()->id()) === $user->id)>{{ $user->displayName() }} — {{ $user->department?->code }}</option>@endforeach</select></div>
            </div>
            <div class="flex flex-wrap gap-3"><button class="dgcpt-btn-primary" type="submit">Créer l’instruction</button><a class="dgcpt-btn-outline" href="{{ route('administrative-work.index') }}">Annuler</a></div>
        </form>
    </div>
</x-app-layout>
