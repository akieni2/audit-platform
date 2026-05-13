<?php

namespace App\Domain\Questionnaires\Events;

use App\Models\Entretien;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EntretienResponsesRecorded
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  list<int>  $responseIds
     * @param  list<int>  $identifiedRiskIds
     */
    public function __construct(
        public Entretien $entretien,
        public array $responseIds,
        public array $identifiedRiskIds,
    ) {}
}
