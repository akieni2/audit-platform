<div class="dgcpt-surface p-6 shadow-sm">
    <p class="dgcpt-card-title">Validation hierarchique</p>
    <form method="POST" action="{{ route('raci.validation', $mission) }}" class="mt-4 grid gap-4 md:grid-cols-[1fr,1fr,auto]">
        @csrf
        <div>
            <label class="dgcpt-label">Affectation</label>
            <select name="raci_assignment_id" class="dgcpt-input">
                @foreach ($raciView['assignments'] as $assignment)
                    <option value="{{ $assignment->id }}">{{ $assignment->process_label }} - {{ $assignment->raciRole?->name ?? 'Role' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="dgcpt-label">Statut</label>
            <select name="status" class="dgcpt-input">
                <option value="pending">En attente</option>
                <option value="approved">Approuve</option>
                <option value="rejected">Rejete</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="dgcpt-btn-primary">Valider</button>
        </div>
        <div class="md:col-span-3">
            <label class="dgcpt-label">Notes</label>
            <textarea name="notes" rows="3" class="dgcpt-textarea"></textarea>
        </div>
    </form>
</div>
