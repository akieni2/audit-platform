<?php

namespace App\Services\Audit;

use App\Models\ImmutableAuditEvent;
use App\Models\RuntimeSecurityEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class RuntimeForensicsService
{
    public function __construct(
        private ImmutableAuditTrailService $auditTrail,
        private AuditIntegrityService $integrity,
    ) {}

    /**
     * @return array{audit: Collection, security: Collection, integrity: array}
     */
    public function buildReplay(?int $missionId = null, int $limit = 40): array
    {
        $audit = $this->auditTrail->timeline(null, $limit);

        $security = Schema::hasTable('runtime_security_events')
            ? RuntimeSecurityEvent::query()
                ->when($missionId, fn ($q) => $q->where('mission_id', $missionId))
                ->latest('occurred_at')
                ->limit($limit)
                ->get()
            : collect();

        return [
            'audit' => $audit,
            'security' => $security,
            'integrity' => $this->integrity->verifyChain(),
        ];
    }

    public function findTamperGaps(): array
    {
        return $this->integrity->detectTampering();
    }
}
