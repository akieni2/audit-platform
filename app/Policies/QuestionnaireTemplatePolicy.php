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
        if ($user->canManageQuestionnaireLibrary()) {
            if ($user->canSuperviseAllDepartments()) {
                return true;
            }

            return $this->userCanEditTemplateScope($user, $template);
        }

        return $template->active
            && $template->isVisibleToDepartment($user->department_id !== null ? (int) $user->department_id : null);
    }

    public function create(User $user): bool
    {
        return $user->canManageQuestionnaireLibrary();
    }

    public function update(User $user, QuestionnaireTemplate $template): bool
    {
        if (! $user->canManageQuestionnaireLibrary()) {
            return false;
        }

        return $this->userCanEditTemplateScope($user, $template);
    }

    public function delete(User $user, QuestionnaireTemplate $template): bool
    {
        return $this->update($user, $template);
    }

    public function duplicate(User $user, QuestionnaireTemplate $template): bool
    {
        return $this->update($user, $template);
    }

    private function userCanEditTemplateScope(User $user, QuestionnaireTemplate $template): bool
    {
        if ($user->canSuperviseAllDepartments()) {
            return true;
        }

        $scope = $template->department_scope;
        if ($scope === null || $scope === []) {
            return false;
        }

        $deptId = $user->department_id !== null ? (int) $user->department_id : null;
        if ($deptId === null) {
            return false;
        }

        return $user->isDepartmentSupervisorOf($deptId)
            && in_array($deptId, array_map('intval', $scope), true);
    }
}
