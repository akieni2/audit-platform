<?php

namespace App\Domain\Questionnaires\Events;

use App\Contracts\Runtime\StructuredBusinessEvent;
use App\Models\Entretien;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class EntretienResponsesRecorded implements StructuredBusinessEvent
{
    use Dispatchable;
    use SerializesModels;

    public bool $afterCommit = true;

    /**
     * @param  list<int>  $responseIds
     * @param  list<int>  $identifiedRiskIds
     */
    public function __construct(
        public Entretien $entretien,
        public array $responseIds,
        public array $identifiedRiskIds,
        public ?string $correlationId = null,
    ) {
        $this->correlationId ??= (string) Str::uuid();
    }

    public function eventName(): string
    {
        return 'questionnaire.responses_recorded';
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
            'response_ids' => $this->responseIds,
            'identified_risk_ids' => $this->identifiedRiskIds,
        ];
    }
}
