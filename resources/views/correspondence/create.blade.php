<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-6 py-2">
        <div>
            <p class="dgcpt-card-title">Bureau du courrier</p>
            <h1 class="dgcpt-page-title">Enregistrer un courrier</h1>
            <p class="mt-1 text-sm dgcpt-text-muted">La référence, le délai SLA et le jeton de traçabilité seront générés automatiquement.</p>
        </div>

        <form method="post" action="{{ route('correspondence.store') }}" enctype="multipart/form-data" class="dgcpt-surface space-y-5 p-6">
            @csrf
            @if ($errors->any())<div class="rounded-lg bg-red-950/40 p-3 text-sm text-red-300">{{ $errors->first() }}</div>@endif
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="dgcpt-label">Sens</label><select name="direction" class="dgcpt-select"><option value="incoming">Courrier entrant</option><option value="outgoing">Courrier sortant</option></select></div>
                <div><label class="dgcpt-label">Date et heure de réception</label><input type="datetime-local" name="received_at" value="{{ old('received_at', now()->format('Y-m-d\TH:i')) }}" class="dgcpt-input" required></div>
                <div><label class="dgcpt-label">Expéditeur</label><input name="sender" value="{{ old('sender') }}" class="dgcpt-input" required></div>
                <div><label class="dgcpt-label">Destinataire</label><input name="recipient" value="{{ old('recipient') }}" class="dgcpt-input"></div>
            </div>
            <div><label class="dgcpt-label">Objet</label><input name="subject" value="{{ old('subject') }}" class="dgcpt-input" required></div>
            <div><label class="dgcpt-label">Description</label><textarea name="description" rows="4" class="dgcpt-textarea">{{ old('description') }}</textarea></div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><label class="dgcpt-label">Type de document</label><input name="document_type" value="{{ old('document_type') }}" class="dgcpt-input" placeholder="Lettre, note, demande…"></div>
                <div><label class="dgcpt-label">Confidentialité</label><select name="confidentiality" class="dgcpt-select"><option value="normal">Normale</option><option value="confidential">Confidentielle</option><option value="secret">Secrète</option></select></div>
                <div><label class="dgcpt-label">Urgence</label><select name="urgency" class="dgcpt-select">@foreach (\App\Models\CorrespondenceRecord::urgencyLabels() as $value => $label)<option value="{{ $value }}" @selected(old('urgency', 'normal') === $value)>{{ $label }}</option>@endforeach</select></div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="dgcpt-label">Direction ou structure</label><select name="current_department_id" class="dgcpt-select"><option value="">Bureau du courrier</option>@foreach ($departments as $department)<option value="{{ $department->id }}">{{ $department->code }} — {{ $department->name }}</option>@endforeach</select></div>
                <div><label class="dgcpt-label">Responsable ou agent</label><select name="current_assignee_id" class="dgcpt-select"><option value="">Non affecté</option>@foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->displayName() }} — {{ $user->department?->code }}</option>@endforeach</select></div>
            </div>
            <div><label class="dgcpt-label">Document numérisé PDF (20 Mo maximum)</label><input type="file" name="document" accept="application/pdf" class="dgcpt-input"></div>
            <div class="flex flex-wrap gap-3"><button class="dgcpt-btn-primary" type="submit">Enregistrer</button><a href="{{ route('correspondence.index') }}" class="dgcpt-btn-outline">Annuler</a></div>
        </form>
    </div>
</x-app-layout>
