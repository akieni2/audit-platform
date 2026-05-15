<?php

namespace App\Services\Audit;

use App\Models\ImmutableAuditEvent;
use Illuminate\Support\Facades\Schema;

class AuditIntegrityService
{
    public function verifyChain(int $sampleSize = 100): array
    {
        if (! Schema::hasTable('immutable_audit_events')) {
            return ['verified' => true, 'checked' => 0, 'broken_at' => null];
        }

        $events = ImmutableAuditEvent::query()->orderBy('id')->limit($sampleSize)->get();
        $previous = null;
        $brokenAt = null;

        foreach ($events as $event) {
            if ($previous !== null && $event->previous_hash !== $previous) {
                $brokenAt = $event->id;
                break;
            }
            $previous = $event->integrity_hash;
        }

        return [
            'verified' => $brokenAt === null,
            'checked' => $events->count(),
            'broken_at' => $brokenAt,
        ];
    }

    public function detectTampering(): array
    {
        $result = $this->verifyChain(500);

        if ($result['verified']) {
            return [];
        }

        return [
            [
                'type' => 'hash_chain_break',
                'event_id' => $result['broken_at'],
            ],
        ];
    }
}
