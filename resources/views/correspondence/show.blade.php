<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 py-2">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="dgcpt-card-title">{{ $record->reference }}</p><h1 class="dgcpt-page-title">{{ $record->subject }}</h1><p class="mt-1 text-sm dgcpt-text-muted">{{ $record->sender }} — reçu le {{ $record->received_at->format('d/m/Y à H:i') }}</p></div>
            <div class="flex flex-wrap gap-2">
                @if ($record->document_path)<a class="dgcpt-btn-outline" href="{{ route('correspondence.download', $record) }}">Télécharger le PDF</a>@endif
                @can('accessAdministrativeWork')<a class="dgcpt-btn-primary" href="{{ route('administrative-work.create', ['correspondence' => $record->id]) }}">Créer une instruction</a>@endcan
            </div>
        </div>
        @if (session('status'))<div class="dgcpt-surface border-[#00A86B]/35 p-4 text-sm">{{ session('status') }}</div>@endif

        <div class="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
            <div class="space-y-6">
                <section class="dgcpt-surface p-6">
                    <h2 class="text-lg font-bold">Fiche du courrier</h2>
                    <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                        <div><dt class="dgcpt-card-title">Statut</dt><dd>{{ \App\Models\CorrespondenceRecord::statusLabels()[$record->status] }}</dd></div>
                        <div><dt class="dgcpt-card-title">Urgence</dt><dd>{{ \App\Models\CorrespondenceRecord::urgencyLabels()[$record->urgency] }}</dd></div>
                        <div><dt class="dgcpt-card-title">Affectation</dt><dd>{{ $record->department?->name ?? 'Bureau du courrier' }} / {{ $record->assignee?->displayName() ?? 'Non affecté' }}</dd></div>
                        <div><dt class="dgcpt-card-title">Échéance</dt><dd class="{{ $record->isOverdue() ? 'text-[#FF5A5A]' : '' }}">{{ $record->deadline_at?->format('d/m/Y H:i') }}</dd></div>
                    </dl>
                    @if ($record->description)<p class="mt-5 whitespace-pre-line text-sm text-[#BFD2E6]">{{ $record->description }}</p>@endif
                    <div class="mt-5 rounded-xl border border-[rgba(0,209,255,.2)] p-4"><p class="dgcpt-card-title">Identifiant de traçabilité QR</p><p class="mt-2 break-all font-mono text-xs text-[#73D8FF]">{{ $record->qr_token }}</p></div>
                </section>

                <section class="dgcpt-surface p-6">
                    <h2 class="text-lg font-bold">Historique des mouvements</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($record->movements as $movement)
                            <article class="rounded-xl border border-[rgba(0,209,255,.12)] p-4 text-sm">
                                <div class="flex flex-wrap justify-between gap-2"><strong>{{ ucfirst(str_replace('_', ' ', $movement->event_type)) }}</strong><time class="text-xs text-[#9FB3C8]">{{ $movement->occurred_at->format('d/m/Y H:i') }}</time></div>
                                <p class="mt-1 text-[#BFD2E6]">{{ $movement->notes }}</p>
                                <p class="mt-1 text-xs text-[#9FB3C8]">{{ $movement->actor?->displayName() }} → {{ $movement->toDepartment?->name ?? $movement->toUser?->displayName() ?? 'Bureau du courrier' }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <form method="post" action="{{ route('correspondence.assign', $record) }}" class="dgcpt-surface space-y-4 p-5">
                    @csrf @method('patch')
                    <h2 class="font-bold">Orienter le courrier</h2>
                    <div><label class="dgcpt-label">Structure</label><select name="department_id" class="dgcpt-select"><option value="">Bureau du courrier</option>@foreach ($departments as $department)<option value="{{ $department->id }}" @selected($record->current_department_id === $department->id)>{{ $department->code }} — {{ $department->name }}</option>@endforeach</select></div>
                    <div><label class="dgcpt-label">Agent</label><select name="assignee_id" class="dgcpt-select"><option value="">Non affecté</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected($record->current_assignee_id === $user->id)>{{ $user->displayName() }} — {{ $user->department?->code }}</option>@endforeach</select></div>
                    <div><label class="dgcpt-label">Instruction</label><textarea name="notes" class="dgcpt-textarea" rows="3"></textarea></div>
                    <button class="dgcpt-btn-primary" type="submit">Affecter</button>
                </form>
                <form method="post" action="{{ route('correspondence.status', $record) }}" class="dgcpt-surface space-y-4 p-5">
                    @csrf @method('patch')
                    <h2 class="font-bold">Mettre à jour le statut</h2>
                    <select name="status" class="dgcpt-select">@foreach (\App\Models\CorrespondenceRecord::statusLabels() as $value => $label)<option value="{{ $value }}" @selected($record->status === $value)>{{ $label }}</option>@endforeach</select>
                    <textarea name="notes" class="dgcpt-textarea" rows="2" placeholder="Observation facultative"></textarea>
                    <button class="dgcpt-btn-outline" type="submit">Enregistrer le statut</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
