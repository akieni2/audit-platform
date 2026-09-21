<x-app-layout>
    @php
        /** @var \App\Models\Mission $mission */
        /** @var \App\Models\MissionService $service */
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $documents */
    @endphp

    <div class="mx-auto max-w-5xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Porte-documents</p>
                <h1 class="dgcpt-page-title">{{ $service->nom }}</h1>
                <p class="text-sm text-[#9FB3C8]">{{ $mission->organisation }}</p>
            </div>
            <a href="{{ route('services.index', $mission) }}" class="dgcpt-btn-outline text-sm">← Services</a>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['Total', $requestStats['total'], '#73D8FF'],
                ['En attente', $requestStats['pending'], '#F4C542'],
                ['Reçus', $requestStats['received'], '#00A86B'],
                ['En retard', $requestStats['overdue'], '#FF5A5A'],
                ['Clôturés', $requestStats['closed'], '#B9A4FF'],
            ] as [$label, $value, $color])
                <div class="dgcpt-surface p-4">
                    <p class="text-xs font-semibold uppercase tracking-widest text-[#9FB3C8]">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold" style="color: {{ $color }}">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        @can('contribute', $service)
            <div class="grid gap-6 lg:grid-cols-2">
                <section class="dgcpt-surface p-6">
                    <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Documents attendus des questionnaires</h2>
                    <p class="mt-2 text-sm text-[#9FB3C8]">Crée les demandes à partir des pièces attendues renseignées dans les questions affectées à ce service.</p>
                    <form method="POST" action="{{ route('missions.services.document-requests.generate', [$mission, $service]) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="dgcpt-btn-primary">Générer les demandes</button>
                    </form>
                </section>

                <section class="dgcpt-surface p-6">
                    <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Demande complémentaire</h2>
                    <form method="POST" action="{{ route('missions.services.document-requests.store', [$mission, $service]) }}" class="mt-4 space-y-3">
                        @csrf
                        <input name="label" required class="dgcpt-input" placeholder="Document ou preuve attendue">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <input name="category" class="dgcpt-input" placeholder="Catégorie ou domaine">
                            <select name="priority" class="dgcpt-select" required>
                                <option value="normal">Priorité normale</option>
                                <option value="important">Priorité importante</option>
                                <option value="critical">Priorité critique</option>
                            </select>
                            <input type="date" name="due_at" class="dgcpt-input">
                            <label class="flex items-center gap-2 text-sm text-[#BFD2E6]"><input type="checkbox" name="is_required" value="1" checked> Pièce obligatoire</label>
                        </div>
                        <textarea name="notes" rows="2" class="dgcpt-textarea" placeholder="Précisions adressées à l’audité"></textarea>
                        <button type="submit" class="dgcpt-btn-outline">Ajouter la demande</button>
                    </form>
                </section>
            </div>
        @endcan

        <section class="dgcpt-surface overflow-hidden p-0">
            <div class="border-b border-[rgba(0,209,255,0.12)] px-6 py-4">
                <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Suivi des demandes documentaires</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="dgcpt-table min-w-full text-sm">
                    <thead><tr><th>Référence et origine</th><th>Document attendu</th><th>Échéance</th><th>État</th><th>Pièces</th><th>Suivi</th></tr></thead>
                    <tbody>
                        @forelse ($documentRequests as $documentRequest)
                            @php
                                $section = $documentRequest->question?->section;
                                $path = collect([$section?->parent?->parent?->title, $section?->parent?->title, $section?->title])->filter()->join(' › ');
                            @endphp
                            <tr>
                                <td class="min-w-52 align-top">
                                    <div class="font-mono text-xs text-[#73D8FF]">{{ $documentRequest->reference }}</div>
                                    <div class="mt-1 text-xs text-[#9FB3C8]">{{ $path ?: ($documentRequest->category ?: 'Demande complémentaire') }}</div>
                                    @if($documentRequest->auditGroup)<div class="mt-1 text-xs text-[#B9A4FF]">{{ $documentRequest->auditGroup->name }}</div>@endif
                                </td>
                                <td class="max-w-md align-top">
                                    <div class="font-semibold text-[#E6EEF8]">{{ $documentRequest->label }}</div>
                                    @if($documentRequest->question)<div class="mt-1 text-xs text-[#9FB3C8]">{{ \Illuminate\Support\Str::limit($documentRequest->question->question, 130) }}</div>@endif
                                    @if($documentRequest->notes)<div class="mt-1 text-xs text-[#BFD2E6]">{{ $documentRequest->notes }}</div>@endif
                                </td>
                                <td class="whitespace-nowrap align-top {{ $documentRequest->isOverdue() ? 'font-semibold text-[#FF7A7A]' : 'text-[#9FB3C8]' }}">
                                    {{ $documentRequest->due_at?->format('d/m/Y') ?: 'Non fixée' }}
                                </td>
                                <td class="whitespace-nowrap align-top"><span class="dgcpt-status-pill">{{ $documentRequest->statusLabel() }}</span></td>
                                <td class="text-center align-top text-[#BFD2E6]">{{ $documentRequest->documents->count() }}</td>
                                <td class="min-w-64 align-top">
                                    @can('update', $documentRequest)
                                        <form method="POST" action="{{ route('mission-document-requests.update', $documentRequest) }}" class="space-y-2">
                                            @csrf @method('PATCH')
                                            <select name="status" class="dgcpt-select text-xs">
                                                @foreach(\App\Models\MissionDocumentRequest::statusLabels() as $value => $label)
                                                    <option value="{{ $value }}" @selected($documentRequest->status === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <input type="date" name="due_at" value="{{ $documentRequest->due_at?->format('Y-m-d') }}" class="dgcpt-input text-xs">
                                            <input name="review_notes" value="{{ $documentRequest->review_notes }}" class="dgcpt-input text-xs" placeholder="Observation de revue">
                                            <button class="dgcpt-btn-outline text-xs">Mettre à jour</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-[#9FB3C8]">Lecture seule</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-8 text-center text-[#9FB3C8]">Aucune demande. Générez-les depuis les questionnaires ou ajoutez une demande complémentaire.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @can('contribute', $service)
            <div class="dgcpt-surface p-6 shadow-sm">
                <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Téléverser</h2>
                <form method="POST" action="{{ route('missions.services.documents.store', [$mission, $service]) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="dgcpt-label" for="file">Fichier (PDF, Office, images, ZIP — max 20 Mo)</label>
                        <input id="file" name="file" type="file" required class="mt-1 block w-full text-sm text-[#E6EEF8]" />
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="dgcpt-label" for="mission_document_request_id">Demande documentaire associée</label>
                            <select id="mission_document_request_id" name="mission_document_request_id" class="dgcpt-select">
                                <option value="">Dépôt libre, sans demande préalable</option>
                                @foreach ($documentRequests as $documentRequest)
                                    <option value="{{ $documentRequest->id }}">{{ $documentRequest->reference }} — {{ \Illuminate\Support\Str::limit($documentRequest->label, 100) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="dgcpt-label" for="category">Catégorie</label>
                            <input id="category" name="category" type="text" class="dgcpt-input" placeholder="ex. preuve, procédure" />
                        </div>
                        <div>
                            <label class="dgcpt-label" for="receipt_status">État de la pièce</label>
                            <select id="receipt_status" name="receipt_status" required class="dgcpt-select">
                                @foreach (\App\Models\MissionDocument::receiptStatusLabels() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="dgcpt-label" for="questionnaire_question_id">Document attendu associé</label>
                            <select id="questionnaire_question_id" name="questionnaire_question_id" class="dgcpt-select">
                                <option value="">Document général de la mission</option>
                                @foreach ($expectedQuestions as $expectedQuestion)
                                    <option value="{{ $expectedQuestion->id }}">{{ $expectedQuestion->section?->title }} — {{ $expectedQuestion->code ?: \Illuminate\Support\Str::limit($expectedQuestion->question, 55) }} — {{ \Illuminate\Support\Str::limit($expectedQuestion->expected_documents, 80) }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($auditGroups->isNotEmpty())
                            <div class="sm:col-span-2">
                                <label class="dgcpt-label" for="mission_audit_group_id">Groupe d’audit</label>
                                <select id="mission_audit_group_id" name="mission_audit_group_id" class="dgcpt-select">
                                    <option value="">Aucun groupe particulier</option>
                                    @foreach ($auditGroups as $auditGroup)
                                        <option value="{{ $auditGroup->id }}">{{ $auditGroup->name }} — {{ $auditGroup->questionnaireTemplate?->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="sm:col-span-2">
                            <label class="dgcpt-label" for="expected_document_label">Intitulé précis de la pièce</label>
                            <input id="expected_document_label" name="expected_document_label" type="text" class="dgcpt-input" placeholder="ex. Schéma directeur du système d’information 2025–2027" />
                        </div>
                    </div>
                    <div>
                        <label class="dgcpt-label" for="description">Description</label>
                        <textarea id="description" name="description" rows="2" class="dgcpt-textarea w-full"></textarea>
                    </div>
                    <button type="submit" class="dgcpt-btn-primary">Envoyer</button>
                </form>
            </div>
        @endcan

        <div class="dgcpt-surface overflow-hidden p-0 shadow-sm">
            <div class="border-b border-[rgba(0,209,255,0.12)] px-6 py-4">
                <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Fichiers</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="dgcpt-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Nom</th>
                            <th class="text-left">Type</th>
                            <th class="text-left">Pièce attendue</th>
                            <th class="text-left">Demande</th>
                            <th class="text-left">État</th>
                            <th class="text-center">Version</th>
                            <th class="text-right">Taille</th>
                            <th class="text-left">Déposé par</th>
                            <th class="text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $doc)
                            <tr>
                                <td class="font-semibold text-[#E6EEF8]"><a href="{{ route('mission-documents.download', $doc) }}" class="text-[#73D8FF] hover:underline">{{ $doc->original_name }}</a></td>
                                <td class="font-mono text-xs text-[#9FB3C8]">{{ $doc->mime_type ?: '—' }}</td>
                                <td class="max-w-xs text-xs text-[#9FB3C8]">{{ $doc->expected_document_label ?: 'Document général' }}</td>
                                <td class="text-xs text-[#73D8FF]">{{ $doc->documentRequest?->reference ?: '—' }}</td>
                                <td class="text-xs text-[#BFD2E6]">{{ \App\Models\MissionDocument::receiptStatusLabels()[$doc->receipt_status] ?? $doc->receipt_status }}</td>
                                <td class="text-center text-[#9FB3C8]">v{{ $doc->version }}</td>
                                <td class="text-right text-[#9FB3C8]">{{ number_format($doc->size / 1024, 1) }} Ko</td>
                                <td class="text-[#9FB3C8]">{{ $doc->uploader?->displayName() ?? '—' }}</td>
                                <td class="text-right">
                                    @can('delete', $doc)
                                        <form method="POST" action="{{ route('mission-documents.destroy', $doc) }}" class="inline" onsubmit="return confirm('Supprimer ce document ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-[#FF5A5A] hover:underline">Supprimer</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-8 text-center text-[#9FB3C8]">Aucun document pour ce service.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-[rgba(0,209,255,0.08)] px-4 py-3">
                {{ $documents->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
