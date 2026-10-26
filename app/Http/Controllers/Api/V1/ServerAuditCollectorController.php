<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServerAuditRunRequest;
use App\Models\Mission;
use App\Services\ServerAudit\CollectorIngestionService;
use Illuminate\Http\JsonResponse;

class ServerAuditCollectorController extends Controller
{
    public function store(StoreServerAuditRunRequest $request, Mission $mission, CollectorIngestionService $ingestion): JsonResponse
    {
        $this->authorize('updateMissionContent', $mission);
        $run = $ingestion->ingest($mission, $request->user(), $request->validated(), $request->ip());

        return response()->json([
            'message' => 'Résultats reçus et analysés.',
            'run_id' => $run->id,
            'run_uuid' => $run->run_uuid,
            'asset_id' => $run->server_audit_asset_id,
            'evidence_document_id' => $run->raw_document_id,
            'summary' => $run->summary,
            'provisional_risk_ids' => $run->results->pluck('identified_risk_id')->filter()->values(),
        ], 201);
    }
}
