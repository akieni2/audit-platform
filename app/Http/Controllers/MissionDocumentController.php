<?php

namespace App\Http\Controllers;

use App\Http\Requests\Services\StoreMissionDocumentRequest;
use App\Models\Mission;
use App\Models\MissionDocument;
use App\Models\MissionService;
use App\Services\Iam\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

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
                ->with('uploader')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        return view('services.documents.index', compact('mission', 'service', 'documents'));
    }

    public function store(StoreMissionDocumentRequest $request, Mission $mission, MissionService $service): RedirectResponse
    {
        abort_unless((int) $service->mission_id === (int) $mission->id, 404);

        if (! Schema::hasTable('mission_documents')) {
            return back()->with('status', 'Porte-documents indisponible sur cette base locale.');
        }

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
        if (! Schema::hasTable('mission_documents')) {
            return back()->with('status', 'Porte-documents indisponible sur cette base locale.');
        }

        $this->authorize('delete', $mission_document);

        $mission_document->delete();

        app(SecurityAuditService::class)->documentDeleted($request->user(), $mission_document, $request);

        return back()->with('status', 'Document supprimé.');
    }
}
