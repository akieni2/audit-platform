<?php

namespace App\Domain\Questionnaires\Events;

use App\Contracts\Runtime\StructuredBusinessEvent;
use App\Models\Entretien;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class QuestionnaireSnapshotCaptured implements StructuredBusinessEvent
{
    use Dispatchable;
    use SerializesModels;

    public bool $afterCommit = true;

    public function __construct(
        public Entretien $entretien,
        public array $snapshot,
        public ?string $correlationId = null,
    ) {
        $this->correlationId ??= (string) Str::uuid();
    }

    public function eventName(): string
    {
        return 'questionnaire.snapshot_captured';
    }

    public function aggregateType(): string
    {
        return 'entretien';
    }

    public function aggregateId(): int|string|null
    {
        return $this->entretien->id;
    }

    public function context(): array
    {
        return [
            'correlation_id' => $this->correlationId,
            'mission_id' => $this->entretien->mission_id,
            'template_id' => $this->snapshot['meta']['template_id'] ?? null,
            'snapshot_hash' => $this->snapshot['meta']['hash'] ?? null,
        ];
    }
}
