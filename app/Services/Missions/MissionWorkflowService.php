<?php

namespace App\Services\Missions;

use App\Models\Mission;
use App\Models\MissionWorkflowEvent;
use App\Models\User;
use App\Notifications\MissionWorkflowNotification;
use App\Services\Runtime\CoreTransactionRunner;
use InvalidArgumentException;

/**
 * Workflow institutionnel : Département → Inspection des Services → COPRI.
 */
class MissionWorkflowService
{
    public function __construct(
        private CoreTransactionRunner $transactions,
    ) {}

    public const ACTION_DEMARRER = 'demarrer';

    public const ACTION_CLOTURER = 'cloturer';

    public const ACTION_VALIDER_IS = 'valider_is';

    public const ACTION_DEMANDER_CORRECTIONS = 'demander_corrections';

    public const ACTION_VALIDER_COPRI = 'valider_copri';

    public const ACTION_RENVOYER_COPRI = 'renvoyer_copri';

    /**
     * @return list<string>
     */
    public function allowedActions(User $actor, Mission $mission): array
    {
        $status = (string) ($mission->mission_status ?? '');
        $allowed = [];

        if ($status === Mission::STATUS_BROUILLON && $this->canOperateDepartmentally($actor, $mission)) {
            $allowed[] = self::ACTION_DEMARRER;
        }

        if ($status === Mission::STATUS_EN_COURS && $this->canOperateDepartmentally($actor, $mission)) {
            $allowed[] = self::ACTION_CLOTURER;
        }

        if ($status === Mission::STATUS_CLOTUREE && $this->isInspectionServices($actor)) {
            $allowed[] = self::ACTION_VALIDER_IS;
            $allowed[] = self::ACTION_DEMANDER_CORRECTIONS;
        }

        if ($status === Mission::STATUS_VALIDEE_IS && $this->isCopri($actor)) {
            $allowed[] = self::ACTION_VALIDER_COPRI;
            $allowed[] = self::ACTION_RENVOYER_COPRI;
        }

        return $allowed;
    }

    public function can(User $actor, Mission $mission, string $action): bool
    {
        return in_array($action, $this->allowedActions($actor, $mission), true);
    }

    /**
     * Exécute une transition et journalise la décision.
     *
     * @throws InvalidArgumentException
     */
    public function transition(User $actor, Mission $mission, string $action, ?string $comment = null): Mission
    {
        if (! $this->can($actor, $mission, $action)) {
            throw new InvalidArgumentException('Transition non autorisée pour ce profil ou cet état.');
        }

        $from = (string) ($mission->mission_status ?? '');
        $to = $this->targetStatus($from, $action);

        return $this->transactions->run(
            name: 'mission.workflow.transition',
            context: [
                'mission_id' => $mission->id,
                'actor_user_id' => $actor->id,
                'action' => $action,
                'from_status' => $from,
                'to_status' => $to,
            ],
            callback: function ($transaction) use ($actor, $mission, $action, $from, $to, $comment) {
                $mission->update(['mission_status' => $to]);

                MissionWorkflowEvent::query()->create([
                    'mission_id' => $mission->id,
                    'user_id' => $actor->id,
                    'action' => $action,
                    'from_status' => $from,
                    'to_status' => $to,
                    'comment' => $comment,
                ]);

                $fresh = $mission->fresh(['auditeur']);
                $transaction->afterCommit(function () use ($fresh, $actor, $action, $comment): void {
                    $this->notifyParticipants($fresh, $actor, $action, $comment);
                });

                return $fresh;
            }
        );
    }

    private function notifyParticipants(Mission $mission, User $actor, string $action, ?string $comment): void
    {
        $mission->loadMissing('auditeur');
        if ($mission->auditeur !== null && ! $mission->auditeur->is($actor)) {
            $mission->auditeur->notify(new MissionWorkflowNotification($mission, $action, $comment, $actor));
        }
    }

    private function targetStatus(string $from, string $action): string
    {
        return match ($action) {
            self::ACTION_DEMARRER => Mission::STATUS_EN_COURS,
            self::ACTION_CLOTURER => Mission::STATUS_CLOTUREE,
            self::ACTION_VALIDER_IS => Mission::STATUS_VALIDEE_IS,
            self::ACTION_DEMANDER_CORRECTIONS => Mission::STATUS_EN_COURS,
            self::ACTION_VALIDER_COPRI => Mission::STATUS_VALIDEE_COPRI,
            self::ACTION_RENVOYER_COPRI => Mission::STATUS_CLOTUREE,
            default => throw new InvalidArgumentException('Action inconnue.'),
        };
    }

    /** Édition des champs métier (hors workflow COPRI). */
    public function canEditMissionBasics(User $user, Mission $mission): bool
    {
        $user->loadMissing('institutionalRole');
        if ($user->institutionalRole?->slug === 'copri') {
            return false;
        }

        if (! Mission::query()->whereKey($mission->id)->visibleToUser($user)->exists()) {
            return false;
        }

        if (($mission->mission_status ?? '') === Mission::STATUS_VALIDEE_COPRI && ! $user->canSuperviseAllDepartments()) {
            return false;
        }

        return $this->canOperateDepartmentally($user, $mission)
            || $this->isInspectionServices($user)
            || $user->canSuperviseAllDepartments();
    }

    protected function canOperateDepartmentally(User $user, Mission $mission): bool
    {
        $user->loadMissing('institutionalRole');

        if ($user->institutionalRole?->slug === 'copri') {
            return false;
        }

        if ($user->canSuperviseAllDepartments() || $user->canSuperviseEntirePole()) {
            return true;
        }

        $uid = $user->department_id;
        if ($uid === null) {
            return false;
        }

        return (int) $mission->department_id === (int) $uid
            || (int) ($mission->supervising_department_id ?? 0) === (int) $uid;
    }

    private function isInspectionServices(User $user): bool
    {
        $user->loadMissing('institutionalRole');
        $slug = $user->institutionalRole?->slug;

        return $slug === 'inspecteur_services'
            || $slug === 'inspecteur_adjoint'
            || $user->hasPermission('validate_mission');
    }

    private function isCopri(User $user): bool
    {
        $user->loadMissing('institutionalRole');

        return $user->institutionalRole?->slug === 'copri';
    }
}
