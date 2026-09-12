<x-app-layout>
@php($labels = \App\Models\Constat::statusLabels())
<div class="mx-auto max-w-7xl space-y-6 px-0 py-2">
    @if(session('status'))<div class="dgcpt-surface border-[#00A86B]/40 p-4 text-[#E6EEF8]">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="dgcpt-surface border-[#FF5A5A]/40 p-4 text-[#FFB4B4]">{{ $errors->first() }}</div>@endif
    <div class="flex flex-wrap justify-between gap-4"><div><p class="dgcpt-card-title">{{ $constat->reference }} · version {{ $constat->version }}</p><h1 class="dgcpt-page-title">Fiche de constat</h1><p class="mt-2 dgcpt-text-muted">{{ $constat->mission->organisation }}</p></div><span class="h-fit rounded-full border border-[#00D1FF]/30 px-4 py-2 text-sm font-bold text-[#00D1FF]">{{ $labels[$constat->status] ?? $constat->status }}</span></div>

    <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <div class="space-y-6">
            <section class="dgcpt-surface space-y-5 p-6">
                <div><p class="dgcpt-label">Critère</p><p class="mt-2 whitespace-pre-wrap text-[#E6EEF8]">{{ $constat->criterion }}</p></div>
                <div><p class="dgcpt-label">Condition observée</p><p class="mt-2 whitespace-pre-wrap text-[#E6EEF8]">{{ $constat->condition_observed ?: $constat->description }}</p></div>
                <div class="grid gap-5 md:grid-cols-2"><div><p class="dgcpt-label">Cause</p><p class="mt-2 whitespace-pre-wrap dgcpt-text-muted">{{ $constat->cause ?: '—' }}</p></div><div><p class="dgcpt-label">Conséquence</p><p class="mt-2 whitespace-pre-wrap dgcpt-text-muted">{{ $constat->consequence ?: '—' }}</p></div></div>
                <div><p class="dgcpt-label">Recommandation proposée</p><p class="mt-2 whitespace-pre-wrap text-[#E6EEF8]">{{ $constat->recommandation }}</p></div>
            </section>

            <section class="dgcpt-surface p-6"><h2 class="text-lg font-bold text-[#E6EEF8]">Éléments probants</h2><div class="mt-4 space-y-2">@forelse($constat->evidences as $doc)<div class="rounded-xl border border-[rgba(148,163,184,.2)] p-3"><span class="text-[#00D1FF]">{{ $doc->original_name }}</span><span class="ml-2 text-xs dgcpt-text-muted">SHA-256 : {{ $doc->checksum_sha256 ?: 'non calculé' }}</span></div>@empty<p class="dgcpt-text-muted">Aucune preuve liée.</p>@endforelse</div></section>

            @if($constat->status === \App\Models\Constat::STATUS_TEAM_REVIEW)
            <section class="dgcpt-surface p-6"><h2 class="text-lg font-bold text-[#E6EEF8]">Revue collaborative</h2><form method="post" action="{{ route('constats.review',$constat) }}" class="mt-4 space-y-3">@csrf<textarea name="comment" class="dgcpt-textarea" placeholder="Avis de l’inspecteur"></textarea><div class="flex gap-3"><button name="decision" value="approved" class="dgcpt-btn-primary">Avis favorable</button><button name="decision" value="changes_requested" class="dgcpt-btn-outline">Demander une modification</button></div></form></section>
            @endif

            @if($constat->status === \App\Models\Constat::STATUS_CONTRADICTORY)
            <section class="dgcpt-surface p-6"><h2 class="text-lg font-bold text-[#E6EEF8]">Réponse contradictoire de l’audité</h2><form method="post" action="{{ route('constats.auditee-response',$constat) }}" class="mt-4 space-y-3">@csrf<select name="position" class="dgcpt-select" required><option value="accepted">Accepté</option><option value="partially_accepted">Partiellement accepté</option><option value="contested">Contesté</option></select><textarea name="observation" class="dgcpt-textarea" required placeholder="Observations de l’audité"></textarea><textarea name="proposed_action" class="dgcpt-textarea" placeholder="Action proposée"></textarea><div class="grid gap-3 md:grid-cols-2"><select name="proposed_owner_user_id" class="dgcpt-select"><option value="">Responsable à déterminer</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->displayName() }}</option>@endforeach</select><input type="date" name="proposed_due_date" class="dgcpt-input"></div><button class="dgcpt-btn-primary">Enregistrer la réponse</button></form></section>
            @endif

            <section class="dgcpt-surface p-6"><h2 class="text-lg font-bold text-[#E6EEF8]">Historique et traçabilité</h2><div class="mt-4 space-y-3">@foreach($constat->reviews as $review)<div class="border-l-2 border-[#00D1FF]/40 pl-4 text-sm"><p class="text-[#E6EEF8]">{{ $review->user?->displayName() ?? 'Système' }} · {{ $review->decision }}</p><p class="dgcpt-text-muted">{{ $review->created_at?->format('d/m/Y H:i') }} — {{ $review->comment ?: 'Sans commentaire' }}</p></div>@endforeach @foreach($constat->auditeeResponses as $response)<div class="border-l-2 border-[#F4D000]/40 pl-4 text-sm"><p class="text-[#E6EEF8]">Réponse de {{ $response->respondent?->displayName() ?? 'Audité' }} · {{ $response->position }}</p><p class="dgcpt-text-muted">{{ $response->observation }}</p></div>@endforeach</div></section>
        </div>

        <aside class="space-y-6">
            <section class="dgcpt-surface p-5"><h2 class="font-bold text-[#E6EEF8]">Circuit du constat</h2><div class="mt-4 space-y-3">
                @can('updateMissionContent',$constat->mission) @if($constat->status === 'draft')<form method="post" action="{{ route('constats.transition',[$constat,'submit_review']) }}">@csrf<button class="dgcpt-btn-primary w-full">Soumettre à la revue</button></form>@endif @endcan
                @can('governMission',$constat->mission)
                    @if($constat->status === 'team_review')<form method="post" action="{{ route('constats.transition',[$constat,'validate_mission']) }}">@csrf<button class="dgcpt-btn-primary w-full">Valider comme chef de mission</button></form>@endif
                    @if($constat->status === 'mission_validated')<form method="post" action="{{ route('constats.transition',[$constat,'open_contradictory']) }}">@csrf<button class="dgcpt-btn-primary w-full">Ouvrir le contradictoire</button></form>@endif
                    @if($constat->status === 'contradictory')<form method="post" action="{{ route('constats.transition',[$constat,'finalize']) }}">@csrf<button class="dgcpt-btn-primary w-full">Rendre définitif</button></form>@endif
                    @if($constat->status === 'final')<form method="post" action="{{ route('constats.convert-risk',$constat) }}">@csrf<button class="dgcpt-btn-primary w-full">Transformer en risque</button></form>@endif
                @endcan
            </div></section>

            <section class="dgcpt-surface p-5"><h2 class="font-bold text-[#E6EEF8]">Recommandations et actions</h2><div class="mt-4 space-y-5">@forelse($constat->recommendations as $rec)<div class="rounded-xl border border-[rgba(148,163,184,.2)] p-4"><p class="font-mono text-xs text-[#00D1FF]">{{ $rec->reference }}</p><p class="mt-2 text-sm text-[#E6EEF8]">{{ $rec->description }}</p><p class="mt-2 text-xs dgcpt-text-muted">État : {{ $rec->status }}</p>
                    @if($rec->status === 'draft')@can('governMission',$constat->mission)<form method="post" action="{{ route('audit-recommendations.validate',$rec) }}" class="mt-3">@csrf<button class="dgcpt-btn-outline w-full">Valider la recommandation</button></form>@endcan@endif
                    @if($rec->status === 'validated')@can('governMission',$constat->mission)<form method="post" action="{{ route('audit-recommendations.actions.store',$rec) }}" class="mt-3 space-y-2">@csrf<textarea name="description" class="dgcpt-textarea" required placeholder="Action corrective"></textarea><input type="date" name="date_echeance" class="dgcpt-input"><button class="dgcpt-btn-primary w-full">Créer l’action</button></form>@endcan@endif
                    @foreach($rec->actions as $action)<div class="mt-3 border-t border-[rgba(148,163,184,.15)] pt-3 text-sm"><p class="text-[#E6EEF8]">{{ $action->description }}</p><p class="dgcpt-text-muted">{{ $action->progress_percent }} % · {{ $action->statut }} · échéance {{ $action->date_echeance?->format('d/m/Y') ?? '—' }}</p>
                        @if($action->statut !== 'ferme')<form method="post" action="{{ route('actions.follow-up',$action) }}" class="mt-3 space-y-2">@csrf @method('PATCH')<div class="grid grid-cols-2 gap-2"><input type="number" min="0" max="100" name="progress_percent" value="{{ $action->progress_percent }}" class="dgcpt-input"><select name="statut" class="dgcpt-select"><option value="ouvert">Ouvert</option><option value="en_cours">En cours</option><option value="bloque">Bloqué</option></select></div><textarea name="comment" class="dgcpt-textarea" placeholder="Point d’avancement"></textarea><div class="space-y-1">@foreach($constat->mission->missionDocuments as $doc)<label class="flex gap-2 text-xs dgcpt-text-muted"><input type="checkbox" name="evidence_ids[]" value="{{ $doc->id }}">{{ $doc->original_name }}</label>@endforeach</div><button class="dgcpt-btn-outline w-full">Enregistrer le suivi</button></form>@endif
                        @if((int)$action->progress_percent === 100 && $action->statut !== 'closure_requested' && $action->statut !== 'ferme')<form method="post" action="{{ route('actions.request-closure',$action) }}" class="mt-2">@csrf<button class="dgcpt-btn-primary w-full">Demander la clôture</button></form>@endif
                        @if($action->statut === 'closure_requested')@can('governMission',$constat->mission)<form method="post" action="{{ route('actions.validate-closure',$action) }}" class="mt-2">@csrf<button class="dgcpt-btn-primary w-full">Valider la clôture</button></form>@endcan@endif
                    </div>@endforeach
                </div>@empty<p class="dgcpt-text-muted">La recommandation institutionnelle sera créée lors de la transformation en risque.</p>@endforelse</div></section>
        </aside>
    </div>
    <a href="{{ route('constats.index',$constat->mission) }}" class="dgcpt-link">← Tous les constats</a>
</div>
</x-app-layout>
