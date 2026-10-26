<?php

namespace App\Services\ServerAudit;

use App\Domain\Risk\Enums\RiskLifecycleStatus;
use App\Models\Constat;
use App\Models\IdentifiedRisk;
use App\Models\Mission;
use App\Models\MissionDocument;
use App\Models\ServerAuditAsset;
use App\Models\ServerAuditControlDefinition;
use App\Models\ServerAuditResult;
use App\Models\ServerAuditRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CollectorIngestionService
{
    public function ingest(Mission $mission, User $actor, array $payload, ?string $sourceIp): ServerAuditRun
    {
        if (ServerAuditRun::query()->where('run_uuid', $payload['run_uuid'])->exists()) {
            throw ValidationException::withMessages(['run_uuid' => 'Cette exécution a déjà été reçue.']);
        }

        $definitions = $this->definitionsFor($payload);
        $this->assertApplicability($payload, $definitions);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $hash = hash('sha256', $json);

        return DB::transaction(function () use ($mission, $actor, $payload, $sourceIp, $json, $hash, $definitions): ServerAuditRun {
            $assetData = $payload['asset'];
            $asset = ServerAuditAsset::query()->updateOrCreate(
                ['mission_id' => $mission->id, 'asset_uuid' => $assetData['uuid']],
                [
                    'hostname' => $assetData['hostname'],
                    'ip_address' => $assetData['ip_address'] ?? null,
                    'platform' => $assetData['platform'],
                    'asset_type' => $assetData['type'] ?? 'server',
                    'product' => $assetData['product'] ?? null,
                    'product_version' => $assetData['product_version'] ?? null,
                    'operating_system' => $assetData['operating_system'] ?? null,
                    'asset_role' => $assetData['role'] ?? null,
                    'metadata' => $assetData['metadata'] ?? null,
                    'last_seen_at' => now(),
                    'active' => true,
                ]
            );

            $path = sprintf('audit-collector/mission-%d/%s.json', $mission->id, $payload['run_uuid']);
            Storage::disk('local')->put($path, $json);
            $document = MissionDocument::query()->create([
                'mission_id' => $mission->id,
                'uploaded_by' => $actor->id,
                'filename' => basename($path),
                'original_name' => 'audit-collector-'.$asset->hostname.'-'.$payload['run_uuid'].'.json',
                'mime_type' => 'application/json',
                'disk' => 'local',
                'path' => $path,
                'checksum_sha256' => $hash,
                'size' => strlen($json),
                'category' => 'server_audit_evidence',
                'receipt_status' => 'to_review',
                'description' => 'Preuve brute transmise automatiquement par le collecteur d’audit.',
                'provided_at' => now(),
                'metadata' => ['run_uuid' => $payload['run_uuid'], 'asset_uuid' => $asset->asset_uuid, 'automated_collection' => true],
            ]);

            $run = ServerAuditRun::query()->create([
                'run_uuid' => $payload['run_uuid'],
                'mission_id' => $mission->id,
                'server_audit_asset_id' => $asset->id,
                'submitted_by' => $actor->id,
                'raw_document_id' => $document->id,
                'collector_version' => $payload['collector_version'],
                'status' => 'processed',
                'source_ip' => $sourceIp,
                'payload_sha256' => $hash,
                'started_at' => $payload['started_at'] ?? null,
                'completed_at' => $payload['completed_at'],
                'summary' => $this->summary($payload['checks']),
            ]);

            foreach ($payload['checks'] as $check) {
                $definition = $definitions[$check['code']];
                $snapshot = $this->definitionSnapshot($definition);
                $risk = $check['status'] === 'fail'
                    ? $this->upsertProvisionalRisk($mission, $asset, $run, $document, $actor, $check, $definition, $snapshot)
                    : null;

                ServerAuditResult::query()->create([
                    'server_audit_run_id' => $run->id,
                    'control_code' => $check['code'],
                    'server_audit_control_definition_id' => $definition->id,
                    'status' => $check['status'],
                    'criticality' => $definition->criticality,
                    'expected' => $check['expected'] ?? $definition->default_expected,
                    'observed' => $check['observed'] ?? null,
                    'raw_output' => $check['raw_output'] ?? null,
                    'identified_risk_id' => $risk?->id,
                    'metadata' => array_merge($check['metadata'] ?? [], ['control_snapshot' => $snapshot]),
                ]);
            }

            return $run->load(['asset', 'results.identifiedRisk', 'rawDocument']);
        });
    }

    private function upsertProvisionalRisk(Mission $mission, ServerAuditAsset $asset, ServerAuditRun $run, MissionDocument $document, User $actor, array $check, ServerAuditControlDefinition $definition, array $snapshot): IdentifiedRisk
    {
        $signature = hash('sha256', implode('|', ['server-audit', $mission->id, $asset->asset_uuid, $check['code']]));
        $metadata = [
            'source' => 'server_audit_collector',
            'control_code' => $check['code'],
            'asset_id' => $asset->id,
            'asset_uuid' => $asset->asset_uuid,
            'hostname' => $asset->hostname,
            'latest_run_id' => $run->id,
            'latest_run_uuid' => $run->run_uuid,
            'evidence_document_id' => $document->id,
            'expected' => $check['expected'] ?? null,
            'observed' => $check['observed'] ?? null,
            'requires_human_validation' => true,
            'control_definition_id' => $definition->id,
            'control_snapshot' => $snapshot,
        ];

        $constat = Constat::query()->updateOrCreate(
            ['reference' => 'CST-AUTO-'.strtoupper(substr($signature, 0, 16))],
            [
                'mission_id' => $mission->id,
                'criterion' => $this->criterion($snapshot),
                'condition_observed' => $check['observed'] ?? 'Voir la preuve brute collectée.',
                'description' => sprintf('%s — %s', $asset->hostname, $definition->title),
                'cause' => null,
                'consequence' => $definition->risk_description ?: 'Risque technique à qualifier par l’équipe d’audit.',
                'gravite' => $definition->criticality,
                'recommandation' => $definition->treatment_guidance,
                'status' => Constat::STATUS_DRAFT,
                'created_by' => $actor->id,
                'metadata' => $metadata,
            ]
        );
        $constat->evidences()->syncWithoutDetaching([
            $document->id => ['linked_by' => $actor->id, 'comment' => 'Preuve brute du collecteur, empreinte SHA-256 '.$run->payload_sha256],
        ]);

        return IdentifiedRisk::query()->updateOrCreate(
            ['mission_id' => $mission->id, 'source_signature' => $signature],
            [
                'constat_id' => $constat->id,
                'title' => $definition->title,
                'description' => sprintf('%s — %s. Valeur observée : %s', $asset->hostname, $definition->title, $check['observed'] ?? 'voir preuve brute'),
                'category' => $definition->category,
                'probability' => 'possible',
                'impact' => in_array($definition->criticality, ['critical', 'high'], true) ? 'high' : 'medium',
                'criticality' => $definition->criticality,
                'lifecycle_status' => RiskLifecycleStatus::Detected->value,
                'recommendation' => $definition->treatment_guidance,
                'ai_generated' => false,
                'validated_by_human' => false,
                'metadata' => $metadata,
                'created_by' => $actor->id,
            ]
        );
    }

    private function definitionsFor(array $payload): array
    {
        return ServerAuditControlDefinition::query()
            ->with('references')->where('active', true)
            ->whereIn('code', collect($payload['checks'])->pluck('code'))
            ->get()->keyBy('code')->all();
    }

    private function assertApplicability(array $payload, array $definitions): void
    {
        $asset = $payload['asset'];
        foreach ($payload['checks'] as $index => $check) {
            $definition = $definitions[$check['code']] ?? null;
            if (! $definition) {
                throw ValidationException::withMessages(["checks.$index.code" => "Le contrôle {$check['code']} n’est pas actif."]);
            }
            $scope = $definition->applicability ?? [];
            foreach (['platforms' => 'platform', 'asset_types' => 'type', 'products' => 'product', 'roles' => 'role'] as $scopeKey => $assetKey) {
                $allowed = $scope[$scopeKey] ?? [];
                $actual = $asset[$assetKey] ?? ($assetKey === 'type' ? 'server' : null);
                if ($actual !== null && $allowed !== [] && ! in_array('*', $allowed, true) && ! in_array($actual, $allowed, true)) {
                    throw ValidationException::withMessages(["checks.$index.code" => "Le contrôle {$check['code']} ne s’applique pas à cet actif ($assetKey: $actual)."]);
                }
            }
        }
    }

    private function definitionSnapshot(ServerAuditControlDefinition $definition): array
    {
        return [
            'code' => $definition->code, 'version' => $definition->version, 'title' => $definition->title,
            'objective' => $definition->control_objective, 'expected' => $definition->default_expected,
            'treatment' => $definition->treatment_guidance,
            'references' => $definition->references->map(fn ($reference) => [
                'authority' => $reference->authority, 'document' => $reference->document_title,
                'version' => $reference->document_version, 'chapter' => $reference->chapter,
                'section' => $reference->section, 'provision' => $reference->provision_reference,
                'requirement_summary' => $reference->requirement_summary, 'source_url' => $reference->source_url,
            ])->values()->all(),
        ];
    }

    private function criterion(array $snapshot): string
    {
        $citations = collect($snapshot['references'])->map(function (array $reference): string {
            return implode(' · ', array_filter([
                $reference['authority'], $reference['document'], $reference['version'],
                $reference['chapter'] ? 'chapitre '.$reference['chapter'] : null,
                $reference['section'] ? 'section '.$reference['section'] : null,
                $reference['provision'] ? 'disposition '.$reference['provision'] : null,
            ]));
        })->implode(' ; ');

        return $citations !== '' ? $citations : ($snapshot['expected'] ?: 'Contrôle '.$snapshot['code']);
    }

    private function summary(array $checks): array
    {
        $summary = ['total' => count($checks), 'pass' => 0, 'fail' => 0, 'error' => 0, 'not_applicable' => 0];
        foreach ($checks as $check) { $summary[$check['status']]++; }
        return $summary;
    }
}
