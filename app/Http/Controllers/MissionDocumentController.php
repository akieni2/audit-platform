<?php

namespace App\Http\Controllers;

use App\Http\Requests\Services\StoreMissionDocumentRequest;
use App\Models\Mission;
use App\Models\MissionDocument;
use App\Models\MissionService;
use App\Models\QuestionnaireQuestion;
use App\Services\Iam\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
                ->with(['uploader', 'questionnaireQuestion.section', 'auditGroup'])
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

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

        return view('services.documents.index', compact('mission', 'service', 'documents', 'expectedQuestions', 'auditGroups'));
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
            'uploaded_by' => $request->user()?->id,
            'filename' => basename($storedPath),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'disk' => $disk,
            'path' => $storedPath,
            'checksum_sha256' => hash_file('sha256', Storage::disk($disk)->path($storedPath)),
            'size' => $file->getSize(),
            'category' => $request->input('category'),
            'expected_document_label' => $request->input('expected_document_label') ?: $question?->expected_documents,
            'receipt_status' => $request->input('receipt_status', 'received'),
            'description' => $request->input('description'),
            'version' => $version,
            'provided_at' => now(),
            'metadata' => [
                'checksum' => null,
            ],
        ]);

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
}
