<?php

namespace App\Policies;

use App\Models\MissionDocumentRequest;
use App\Models\User;

class MissionDocumentRequestPolicy
{
    public function view(User $user, MissionDocumentRequest $documentRequest): bool
    {
        return $documentRequest->mission !== null
            && app(MissionPolicy::class)->view($user, $documentRequest->mission);
    }

    public function update(User $user, MissionDocumentRequest $documentRequest): bool
    {
        return $documentRequest->mission !== null
            && app(MissionPolicy::class)->updateMissionContent($user, $documentRequest->mission);
    }
}
