<?php

namespace App\Services\Ai\Knowledge;

use App\Models\AiRecommendation;
use App\Models\Mission;
use Illuminate\Support\Facades\Schema;

class HistoricalLearningService
{
    public function similarMissions(Mission $mission, int $limit = 5): array
    {
        return Mission::query()
            ->where('department_id', $mission->department_id)
            ->whereKeyNot($mission->id)
            ->latest('id')
            ->limit($limit)
            ->get(['id', 'organisation', 'mission_status'])
            ->all();
    }

    public function pastRecommendations(Mission $mission, int $limit = 10): array
    {
        if (! Schema::hasTable('ai_recommendations')) {
            return [];
        }

        return AiRecommendation::query()
            ->where('mission_id', $mission->id)
            ->latest('id')
            ->limit($limit)
            ->get()
            ->all();
    }
}
