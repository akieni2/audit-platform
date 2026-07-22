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
                                <td colspan="9" class="py-8 text-center text-[#9FB3C8]">Aucun document pour ce service.</td>
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
