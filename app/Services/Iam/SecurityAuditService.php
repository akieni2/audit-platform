<?php

namespace App\Services\Iam;

use App\Models\AuditLog;
use App\Models\Mission;
use App\Models\MissionTeamMember;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Journalisation sécurité (connexions, IAM, conformité OWASP traceability).
 */
class SecurityAuditService
{
    public function log(
        string $action,
        string $module,
        ?string $description,
        ?User $user,
        Request $request,
        ?array $metadata = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $user?->id,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    public function loginSuccess(User $user, Request $request): AuditLog
    {
        return $this->log(
            'login_success',
            'auth',
            'Connexion réussie — '.$user->email,
            $user,
            $request,
            ['email' => $user->email],
        );
    }

    public function loginFailure(string $email, Request $request, ?string $reason = null): AuditLog
    {
        return $this->log(
            'login_failure',
            'auth',
            $reason ?? 'Échec authentification — '.$email,
            null,
            $request,
            ['email' => $email],
        );
    }

    public function logout(User $user, Request $request): AuditLog
    {
        return $this->log(
            'logout',
            'auth',
            'Déconnexion — '.$user->email,
            $user,
            $request,
        );
    }

    public function passwordChanged(User $user, Request $request): AuditLog
    {
        return $this->log(
            'password_changed',
            'iam',
            'Mot de passe modifié — '.$user->email,
            $user,
            $request,
        );
    }

    public function passwordResetCompleted(User $user, Request $request): AuditLog
    {
        return $this->log(
            'password_reset_completed',
            'auth',
            'Réinitialisation mot de passe (lien) — '.$user->email,
            $user,
            $request,
        );
    }

    public function accountLocked(User $user, Request $request): AuditLog
    {
        return $this->log(
            'account_locked',
            'iam',
            'Compte verrouillé après échecs — '.$user->email,
            $user,
            $request,
        );
    }

    public function userCreated(User $actor, User $target, Request $request): AuditLog
    {
        return $this->log(
            'user_created',
            'iam',
            'Utilisateur créé — '.$target->email,
            $actor,
            $request,
            ['target_user_id' => $target->id],
        );
    }

    public function userUpdated(User $actor, User $target, Request $request): AuditLog
    {
        return $this->log(
            'user_updated',
            'iam',
            'Utilisateur modifié — '.$target->email,
            $actor,
            $request,
            ['target_user_id' => $target->id],
        );
    }

    public function userDeactivated(User $actor, User $target, Request $request): AuditLog
    {
        return $this->log(
            'user_deactivated',
            'iam',
            'Utilisateur désactivé — '.$target->email,
            $actor,
            $request,
            ['target_user_id' => $target->id],
        );
    }

    public function userSoftDeleted(User $actor, User $target, Request $request): AuditLog
    {
        return $this->log(
            'user_soft_deleted',
            'iam',
            'Compte IAM supprimé (soft) — accès révoqué, traces conservées — '.$target->email,
            $actor,
            $request,
            [
                'target_user_id' => $target->id,
                'deleted_by' => $actor->id,
            ],
        );
    }

    public function permissionsUpdated(User $actor, User $target, Request $request, array $context = []): AuditLog
    {
        return $this->log(
            'permissions_updated',
            'iam',
            'Rôle / permissions affectés — '.$target->email,
            $actor,
            $request,
            array_merge(['target_user_id' => $target->id], $context),
        );
    }

    /** Changement de rattachement IAM (rôle institutionnel, département). */
    public function iamAttributesChanged(User $actor, User $target, Request $request, array $changes): AuditLog
    {
        return $this->log(
            'iam_attributes_changed',
            'iam',
            'Modification rattachement IAM — '.$target->email,
            $actor,
            $request,
            array_merge(['target_user_id' => $target->id], ['changes' => $changes]),
        );
    }

    /** Refus d’accès (policy / gate) — traçabilité conformité. */
    public function authorizationDenied(?User $user, Request $request, string $ability, array $metadata = []): AuditLog
    {
        return $this->log(
            'authorization_denied',
            'security',
            'Accès refusé — '.$ability,
            $user,
            $request,
            array_merge(['ability' => $ability], $metadata),
        );
    }

    /** Tentative d’accès à une route d’administration sans droit suffisant. */
    public function adminRouteDenied(?User $user, Request $request, string $routeName): AuditLog
    {
        return $this->log(
            'admin_route_denied',
            'security',
            'Tentative accès administration — '.$routeName,
            $user,
            $request,
            ['route' => $routeName],
        );
    }

    /** Mise à jour ordre de mission / champs institutionnels (traçabilité). */
    public function missionOrdreUpdated(User $actor, Mission $mission, Request $request, array $fields): AuditLog
    {
        return $this->log(
            'mission_ordre_updated',
            'missions',
            'Ordre de mission / fiche — mission #'.$mission->id,
            $actor,
            $request,
            [
                'mission_id' => $mission->id,
                'fields' => $fields,
            ],
        );
    }

    public function missionTeamMemberAssigned(User $actor, Mission $mission, MissionTeamMember $member, Request $request): AuditLog
    {
        return $this->log(
            'mission_team_member_assigned',
            'missions',
            'Affectation équipe mission — mission #'.$mission->id,
            $actor,
            $request,
            [
                'mission_id' => $mission->id,
                'member_id' => $member->id,
                'user_id' => $member->user_id,
                'mission_role' => $member->mission_role,
            ],
        );
    }

    public function missionTeamMemberRemoved(User $actor, Mission $mission, MissionTeamMember $member, Request $request): AuditLog
    {
        return $this->log(
            'mission_team_member_removed',
            'missions',
            'Retrait équipe mission — mission #'.$mission->id,
            $actor,
            $request,
            [
                'mission_id' => $mission->id,
                'member_id' => $member->id,
                'user_id' => $member->user_id,
                'mission_role' => $member->mission_role,
            ],
        );
    }
}
