<?php

namespace App\Http\Controllers;

use App\Http\Requests\Services\StoreMissionDocumentRequest;
use App\Models\Mission;
use App\Models\MissionDocument;
use App\Models\MissionDocumentRequest;
use App\Models\MissionService;
use App\Models\QuestionnaireQuestion;
use App\Services\Iam\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MissionDocumentController extends Controller
{
    public function index(Request $request, Mission $mission, MissionService $service): View
    {
        abort_unless((int) $service->mission_id === (int) $mission->id, 404);
        $this->authorize('view', $mission);

        if (! Schema::hasTable('mission_documents')) {
            $documents = new LengthAwarePaginator(
                collect(),
                0,
                20,
                LengthAwarePaginator::resolveCurrentPage(),
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        } else {
            $documents = MissionDocument::query()
                ->where('mission_id', $mission->id)
                ->where('service_id', $service->id)
                ->with(['uploader', 'questionnaireQuestion.section', 'auditGroup', 'documentRequest'])
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        $documentRequests = Schema::hasTable('mission_document_requests')
            ? MissionDocumentRequest::query()
                ->where('mission_id', $mission->id)
                ->where('service_id', $service->id)
                ->with(['question.section.parent.parent', 'auditGroup.questionnaireTemplate', 'requester', 'assignee', 'documents'])
                ->orderByRaw('due_at is null')
                ->orderBy('due_at')
                ->orderBy('id')
                ->get()
            : collect();

        $requestStats = [
            'total' => $documentRequests->count(),
            'received' => $documentRequests->whereIn('status', [MissionDocumentRequest::STATUS_RECEIVED, MissionDocumentRequest::STATUS_TO_REVIEW, MissionDocumentRequest::STATUS_ACCEPTED])->count(),
            'pending' => $documentRequests->whereIn('status', [MissionDocumentRequest::STATUS_DRAFT, MissionDocumentRequest::STATUS_REQUESTED, MissionDocumentRequest::STATUS_PENDING])->count(),
            'overdue' => $documentRequests->filter->isOverdue()->count(),
            'closed' => $documentRequests->whereIn('status', MissionDocumentRequest::closedStatuses())->count(),
        ];

        $templateIds = $mission->auditGroups()->pluck('questionnaire_template_id')
            ->merge($service->entretiens()->whereNotNull('questionnaire_template_id')->pluck('questionnaire_template_id'))
            ->unique()
            ->values();
        $expectedQuestions = QuestionnaireQuestion::query()
            ->whereHas('section', fn ($query) => $query->whereIn('questionnaire_template_id', $templateIds))
            ->whereNotNull('expected_documents')
            ->with('section')
            ->orderBy('code')
            ->get();
        $auditGroups = $mission->auditGroups()->with('questionnaireTemplate')->orderBy('name')->get();

        return view('services.documents.index', compact('mission', 'service', 'documents', 'documentRequests', 'requestStats', 'expectedQuestions', 'auditGroups'));
    }

    public function generate(Request $request, Mission $mission, MissionService $service): RedirectResponse
    {
        abort_unless((int) $service->mission_id === (int) $mission->id, 404);
        $this->authorize('create', [MissionDocument::class, $mission]);

        $groups = $mission->auditGroups()
            ->whereNotNull('questionnaire_template_id')
            ->where(fn ($query) => $query->whereNull('service_id')->orWhere('service_id', $service->id))
            ->get();
        $templateGroups = $groups->groupBy('questionnaire_template_id');
        $templateIds = $templateGroups->keys()
            ->merge($service->entretiens()->whereNotNull('questionnaire_template_id')->pluck('questionnaire_template_id'))
            ->unique()
            ->values();

        $questions = QuestionnaireQuestion::query()
            ->whereHas('section', fn ($query) => $query->whereIn('questionnaire_template_id', $templateIds))
            ->whereNotNull('expected_documents')
            ->with('section')
            ->get();
        $created = 0;

        foreach ($questions as $question) {
            $labels = $this->expectedDocumentLabels((string) $question->expected_documents);
            $group = $templateGroups->get($question->section?->questionnaire_template_id)?->first();
            foreach ($labels as $label) {
                $exists = MissionDocumentRequest::query()
                    ->where('mission_id', $mission->id)
                    ->where('service_id', $service->id)
                    ->where('questionnaire_question_id', $question->id)
                    ->where('label', $label)
                    ->exists();
                if ($exists) {
                    continue;
                }

                MissionDocumentRequest::query()->create([
                    'mission_id' => $mission->id,
                    'service_id' => $service->id,
                    'questionnaire_question_id' => $question->id,
                    'mission_audit_group_id' => $group?->id,
                    'requested_by' => $request->user()?->id,
                    'reference' => $this->newRequestReference($mission),
                    'label' => $label,
                    'category' => 'Pièce attendue du questionnaire',
                    'status' => MissionDocumentRequest::STATUS_DRAFT,
                    'priority' => 'normal',
                    'is_required' => (bool) $question->required,
                    'metadata' => ['generated_from_questionnaire' => true],
                ]);
                $created++;
            }
        }

        return back()->with('status', $created > 0
            ? $created.' demande(s) documentaire(s) générée(s) depuis les questionnaires.'
            : 'Aucune nouvelle demande à générer : les documents attendus sont déjà présents ou aucun questionnaire n’est affecté à ce service.');
    }

    public function storeRequest(Request $request, Mission $mission, MissionService $service): RedirectResponse
    {
        abort_unless((int) $service->mission_id === (int) $mission->id, 404);
        $this->authorize('create', [MissionDocument::class, $mission]);
        $data = $request->validate([
            'label' => ['required', 'string', 'max:512'],
            'category' => ['nullable', 'string', 'max:128'],
            'priority' => ['required', 'in:normal,important,critical'],
            'due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'is_required' => ['nullable', 'boolean'],
        ]);

        MissionDocumentRequest::query()->create([
            ...$data,
            'mission_id' => $mission->id,
            'service_id' => $service->id,
            'requested_by' => $request->user()?->id,
            'reference' => $this->newRequestReference($mission),
            'status' => MissionDocumentRequest::STATUS_DRAFT,
            'is_required' => $request->boolean('is_required'),
        ]);

        return back()->with('status', 'Demande documentaire ajoutée.');
    }

    public function updateRequest(Request $request, MissionDocumentRequest $document_request): RedirectResponse
    {
        $this->authorize('update', $document_request);
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(MissionDocumentRequest::statusLabels()))],
            'due_at' => ['nullable', 'date'],
            'review_notes' => ['nullable', 'string', 'max:3000'],
        ]);
        if (in_array($data['status'], [MissionDocumentRequest::STATUS_REQUESTED, MissionDocumentRequest::STATUS_PENDING], true)
            && $document_request->requested_at === null) {
            $data['requested_at'] = now();
        }
        if (in_array($data['status'], [MissionDocumentRequest::STATUS_ACCEPTED, MissionDocumentRequest::STATUS_REJECTED], true)) {
            $data['reviewed_at'] = now();
        }
        $data['closed_at'] = in_array($data['status'], MissionDocumentRequest::closedStatuses(), true) ? now() : null;
        $document_request->update($data);

        return back()->with('status', 'Suivi documentaire mis à jour.');
    }

    public function store(StoreMissionDocumentRequest $request, Mission $mission, MissionService $service): RedirectResponse
    {
        abort_unless((int) $service->mission_id === (int) $mission->id, 404);

        if (! Schema::hasTable('mission_documents')) {
            return back()->with('status', 'Porte-documents indisponible sur cette base locale.');
        }

        $file = $request->file('file');
        $question = $request->filled('questionnaire_question_id')
            ? QuestionnaireQuestion::query()->with('section')->findOrFail((int) $request->input('questionnaire_question_id'))
            : null;
        abort_if($question && ! $this->questionBelongsToMission($question, $mission, $service), 422, 'Cette pièce attendue ne relève pas de la mission.');
        $auditGroupId = $request->integer('mission_audit_group_id') ?: null;
        abort_if($auditGroupId && ! $mission->auditGroups()->whereKey($auditGroupId)->exists(), 422, 'Ce groupe ne relève pas de la mission.');
        $documentRequest = $request->filled('mission_document_request_id')
            ? MissionDocumentRequest::query()
                ->where('mission_id', $mission->id)
                ->where('service_id', $service->id)
                ->findOrFail($request->integer('mission_document_request_id'))
            : null;
        if ($documentRequest) {
            $question = $documentRequest->question;
            $auditGroupId = $documentRequest->mission_audit_group_id;
        }
        $disk = 'local';
        $dir = 'mission_documents/'.$mission->id.'/'.$service->id;
        $storedPath = $file->store($dir, $disk);

        $version = MissionDocument::query()
            ->where('mission_id', $mission->id)
            ->where('service_id', $service->id)
            ->when($question, fn ($query) => $query->where('questionnaire_question_id', $question->id))
            ->max('version') + 1;
        $doc = MissionDocument::query()->create([
            'mission_id' => $mission->id,
            'service_id' => $service->id,
            'entretien_id' => null,
            'questionnaire_question_id' => $question?->id,
            'mission_audit_group_id' => $auditGroupId,
            'mission_document_request_id' => $documentRequest?->id,
            'uploaded_by' => $request->user()?->id,
            'filename' => basename($storedPath),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'disk' => $disk,
            'path' => $storedPath,
            'checksum_sha256' => hash_file('sha256', Storage::disk($disk)->path($storedPath)),
            'size' => $file->getSize(),
            'category' => $request->input('category'),
            'expected_document_label' => $documentRequest?->label ?: ($request->input('expected_document_label') ?: $question?->expected_documents),
            'receipt_status' => $request->input('receipt_status', 'received'),
            'description' => $request->input('description'),
            'version' => $version,
            'provided_at' => now(),
            'metadata' => [
                'checksum' => null,
            ],
        ]);

        if ($documentRequest) {
            $documentRequest->update([
                'status' => match ($doc->receipt_status) {
                    'partial' => MissionDocumentRequest::STATUS_PARTIAL,
                    'to_review' => MissionDocumentRequest::STATUS_TO_REVIEW,
                    default => MissionDocumentRequest::STATUS_RECEIVED,
                },
            ]);
        }

        app(SecurityAuditService::class)->documentUploaded($request->user(), $doc, $request);

        return back()->with('status', 'Document enregistré.');
    }

    public function download(MissionDocument $mission_document): StreamedResponse
    {
        $this->authorize('view', $mission_document);
        abort_unless(Storage::disk($mission_document->disk)->exists($mission_document->path), 404);

        return Storage::disk($mission_document->disk)->download(
            $mission_document->path,
            $mission_document->original_name,
            ['Content-Type' => $mission_document->mime_type ?: 'application/octet-stream'],
        );
    }

    public function destroy(Request $request, MissionDocument $mission_document): RedirectResponse
    {
        if (! Schema::hasTable('mission_documents')) {
            return back()->with('status', 'Porte-documents indisponible sur cette base locale.');
        }

        $this->authorize('delete', $mission_document);

        $mission_document->delete();

        app(SecurityAuditService::class)->documentDeleted($request->user(), $mission_document, $request);

        return back()->with('status', 'Document supprimé.');
    }

    private function questionBelongsToMission(QuestionnaireQuestion $question, Mission $mission, MissionService $service): bool
    {
        $templateId = $question->section?->questionnaire_template_id;
        if ($templateId === null) {
            return false;
        }

        return $mission->auditGroups()->where('questionnaire_template_id', $templateId)->exists()
            || $service->entretiens()->where('questionnaire_template_id', $templateId)->exists();
    }

    /** @return list<string> */
    private function expectedDocumentLabels(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n|;/', $value) ?: [])
            ->map(fn (string $label) => trim(preg_replace('/^\s*(?:[-•]\s*|\d+[.)]\s*)/u', '', $label) ?? $label))
            ->filter()
            ->unique(fn (string $label) => Str::lower($label))
            ->values()
            ->all();
    }

    private function newRequestReference(Mission $mission): string
    {
        do {
            $reference = 'DOC-'.($mission->reference ?: $mission->id).'-'.Str::upper(Str::random(8));
            $reference = Str::limit($reference, 64, '');
        } while (MissionDocumentRequest::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
