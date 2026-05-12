<?php

namespace App\Http\Controllers;

use App\Http\Requests\Services\StoreMissionDocumentRequest;
use App\Models\Mission;
use App\Models\MissionDocument;
use App\Models\MissionService;
use App\Services\Iam\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MissionDocumentController extends Controller
{
    public function index(Request $request, Mission $mission, MissionService $service): View
    {
        abort_unless((int) $service->mission_id === (int) $mission->id, 404);
        $this->authorize('view', $mission);

        $documents = MissionDocument::query()
            ->where('mission_id', $mission->id)
            ->where('service_id', $service->id)
            ->with('uploader')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('services.documents.index', compact('mission', 'service', 'documents'));
    }

    public function store(StoreMissionDocumentRequest $request, Mission $mission, MissionService $service): RedirectResponse
    {
        abort_unless((int) $service->mission_id === (int) $mission->id, 404);

        $file = $request->file('file');
        $disk = 'local';
        $dir = 'mission_documents/'.$mission->id.'/'.$service->id;
        $storedPath = $file->store($dir, $disk);

        $doc = MissionDocument::query()->create([
            'mission_id' => $mission->id,
            'service_id' => $service->id,
            'entretien_id' => null,
            'uploaded_by' => $request->user()?->id,
            'filename' => basename($storedPath),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'disk' => $disk,
            'path' => $storedPath,
            'size' => $file->getSize(),
            'category' => $request->input('category'),
            'description' => $request->input('description'),
            'version' => 1,
            'metadata' => [
                'checksum' => null,
            ],
        ]);

        app(SecurityAuditService::class)->documentUploaded($request->user(), $doc, $request);

        return back()->with('status', 'Document enregistré.');
    }

    public function destroy(Request $request, MissionDocument $mission_document): RedirectResponse
    {
        $this->authorize('delete', $mission_document);

        $mission_document->delete();

        app(SecurityAuditService::class)->documentDeleted($request->user(), $mission_document, $request);

        return back()->with('status', 'Document supprimé.');
    }
}
