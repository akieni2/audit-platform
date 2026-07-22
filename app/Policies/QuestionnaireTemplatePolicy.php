<?php

namespace App\Policies;

use App\Models\QuestionnaireTemplate;
use App\Models\User;

class QuestionnaireTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, QuestionnaireTemplate $template): bool
    {
        if ($template->mission_id === null) {
            return true;
        }

        $template->loadMissing('mission');

        return $template->mission !== null && $user->can('view', $template->mission);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, QuestionnaireTemplate $template): bool
    {
        return $this->userCanEditTemplateScope($user, $template);
    }

    public function delete(User $user, QuestionnaireTemplate $template): bool
    {
        return $this->userCanEditTemplateScope($user, $template);
    }

    public function duplicate(User $user, QuestionnaireTemplate $template): bool
    {
        if ($template->mission_id !== null) {
            return false;
        }

        return $this->userCanEditTemplateScope($user, $template);
    }

    private function userCanEditTemplateScope(User $user, QuestionnaireTemplate $template): bool
    {
        if ($template->mission_id !== null) {
            $template->loadMissing('mission');

            return $template->mission !== null
                && $template->review_status !== QuestionnaireTemplate::REVIEW_ADOPTED
                && $user->can('createQuestionnaire', $template->mission);
        }

        return true;
    }
}
