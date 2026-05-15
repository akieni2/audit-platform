<?php

namespace App\Services\Ai\Observability;

use App\Models\AiConversation;
use App\Models\AiRecommendation;
use Illuminate\Support\Facades\Schema;

class AiUsageAnalyticsService
{
    public function usage(): array
    {
        return [
            'conversations' => Schema::hasTable('ai_conversations') ? AiConversation::query()->count() : 0,
            'recommendations' => Schema::hasTable('ai_recommendations') ? AiRecommendation::query()->count() : 0,
            'pending_validation' => Schema::hasTable('ai_recommendations')
                ? AiRecommendation::query()->where('requires_human_validation', true)->whereNull('accepted')->count()
                : 0,
        ];
    }
}
