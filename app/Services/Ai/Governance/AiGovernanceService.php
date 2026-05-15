<?php

namespace App\Services\Ai\Governance;

use App\Models\Mission;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class AiGovernanceService
{
    public function assertAssistiveRequestAllowed(?User $user, ?Mission $mission = null): void
    {
        if (! config('ai_copilot.enabled', true)) {
            throw new AuthorizationException('Le copilote IA est désactivé.');
        }

        abort_unless($user, 403);

        if ($mission !== null) {
            abort_unless($user->can('view', $mission), 403);
        }

        if (config('ai_copilot.auto_execute_recommendations', false)) {
            throw new AuthorizationException('L\'exécution automatique des recommandations IA est interdite.');
        }

        if (config('ai_copilot.auto_validate_workflow', false)) {
            throw new AuthorizationException('La validation automatique de workflow par IA est interdite.');
        }
    }
}
