<?php

namespace App\Domain\Questionnaires\Events;

use App\Models\Entretien;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuestionnaireSnapshotCaptured
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Entretien $entretien,
        public array $snapshot,
    ) {}
}
