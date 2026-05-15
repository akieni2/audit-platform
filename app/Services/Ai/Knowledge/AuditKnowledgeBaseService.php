<?php

namespace App\Services\Ai\Knowledge;

use App\Models\Mission;

class AuditKnowledgeBaseService
{
    /**
     * @return array<string, mixed>
     */
    public function contextForMission(Mission $mission): array
    {
        return [
            'mission' => [
                'id' => $mission->id,
                'organisation' => $mission->organisation,
                'status' => $mission->mission_status,
            ],
            'patterns' => $this->patterns(),
        ];
    }

    /**
     * @return list<string>
     */
    public function patterns(): array
    {
        return [
            'documentation_incomplete',
            'segregation_of_duties',
            'access_review_gap',
            'change_management_weakness',
        ];
    }
}
