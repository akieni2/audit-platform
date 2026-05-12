<?php

namespace App\Services\Iam;

use App\Models\AuditLog;
use App\Models\DepartmentAuditConsolidation;
use App\Models\Entretien;
use App\Models\EntretienResponse;
use App\Models\IdentifiedRisk;
use App\Models\Mission;
use App\Models\MissionDocument;
use App\Models\MissionTeamMember;
use App\Models\QuestionnaireTemplate;
use App\Models\Service;
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

    public function missionCreated(User $actor, Mission $mission, Request $request): AuditLog
    {
        return $this->log(
            'mission_created',
            'missions',
            'Création mission — #'.$mission->id,
            $actor,
            $request,
            ['mission_id' => $mission->id, 'department_id' => $mission->department_id],
        );
    }

    public function missionDeadlinesUpdated(User $actor, Mission $mission, Request $request, array $fields): AuditLog
    {
        return $this->log(
            'mission_deadlines_updated',
            'missions',
            'Modification délais / période mission — #'.$mission->id,
            $actor,
            $request,
            ['mission_id' => $mission->id, 'fields' => $fields],
        );
    }

    public function missionClosed(User $actor, Mission $mission, Request $request): AuditLog
    {
        return $this->log(
            'mission_closed',
            'missions',
            'Clôture mission (workflow) — #'.$mission->id,
            $actor,
            $request,
            ['mission_id' => $mission->id, 'mission_status' => $mission->mission_status],
        );
    }

    public function missionChefChanged(User $actor, Mission $mission, Request $request, ?int $previousUserId, int $newUserId): AuditLog
    {
        return $this->log(
            'mission_chef_changed',
            'missions',
            'Changement chef de mission — #'.$mission->id,
            $actor,
            $request,
            [
                'mission_id' => $mission->id,
                'previous_auditeur_id' => $previousUserId,
                'new_auditeur_id' => $newUserId,
            ],
        );
    }

    public function missionOperationalContentUpdated(User $actor, Mission $mission, Request $request, array $fields): AuditLog
    {
        return $this->log(
            'mission_operational_content_updated',
            'missions',
            'Mise à jour contenu opérationnel mission — #'.$mission->id,
            $actor,
            $request,
            ['mission_id' => $mission->id, 'fields' => $fields],
        );
    }

    public function questionnaireTemplateCreated(?User $actor, QuestionnaireTemplate $template, Request $request): AuditLog
    {
        return $this->log(
            'questionnaire_template_created',
            'questionnaires',
            'Création modèle questionnaire — '.$template->name,
            $actor,
            $request,
            ['questionnaire_template_id' => $template->id, 'slug' => $template->slug],
        );
    }

    public function questionnaireTemplateUpdated(?User $actor, QuestionnaireTemplate $template, Request $request): AuditLog
    {
        return $this->log(
            'questionnaire_template_updated',
            'questionnaires',
            'Mise à jour modèle questionnaire — '.$template->name,
            $actor,
            $request,
            ['questionnaire_template_id' => $template->id],
        );
    }

    public function entretienResponseCreated(?User $actor, Entretien $entretien, EntretienResponse $response, Request $request): AuditLog
    {
        return $this->log(
            'entretien_response_created',
            'questionnaires',
            'Réponse entretien dynamique — entretien #'.$entretien->id,
            $actor,
            $request,
            [
                'entretien_id' => $entretien->id,
                'entretien_response_id' => $response->id,
                'questionnaire_question_id' => $response->questionnaire_question_id,
            ],
        );
    }

    public function riskIdentified(?User $actor, IdentifiedRisk $risk, Request $request): AuditLog
    {
        return $this->log(
            'risk_identified',
            'questionnaires',
            'Risque identifié — '.$risk->title,
            $actor,
            $request,
            [
                'identified_risk_id' => $risk->id,
                'mission_id' => $risk->mission_id,
                'entretien_id' => $risk->entretien_id,
            ],
        );
    }

    public function riskValidated(?User $actor, IdentifiedRisk $risk, Request $request): AuditLog
    {
        return $this->log(
            'risk_validated',
            'questionnaires',
            'Validation humaine risque — '.$risk->title,
            $actor,
            $request,
            ['identified_risk_id' => $risk->id],
        );
    }

    public function missionServiceCreated(?User $actor, Service $service, Request $request): AuditLog
    {
        return $this->log(
            'mission_service_created',
            'missions',
            'Service audité créé — '.$service->nom.' (mission #'.$service->mission_id.')',
            $actor,
            $request,
            ['service_id' => $service->id, 'mission_id' => $service->mission_id],
        );
    }

    public function missionServiceUpdated(?User $actor, Service $service, Request $request): AuditLog
    {
        return $this->log(
            'mission_service_updated',
            'missions',
            'Service audité mis à jour — '.$service->nom.' (mission #'.$service->mission_id.')',
            $actor,
            $request,
            ['service_id' => $service->id, 'mission_id' => $service->mission_id],
        );
    }

    public function entretienStarted(?User $actor, Entretien $entretien, Request $request): AuditLog
    {
        return $this->log(
            'entretien_started',
            'missions',
            'Entretien démarré — #'.$entretien->id,
            $actor,
            $request,
            ['entretien_id' => $entretien->id, 'mission_id' => $entretien->mission_id, 'service_id' => $entretien->service_id],
        );
    }

    public function entretienCompleted(?User $actor, Entretien $entretien, Request $request): AuditLog
    {
        return $this->log(
            'entretien_completed',
            'missions',
            'Entretien complété — #'.$entretien->id,
            $actor,
            $request,
            ['entretien_id' => $entretien->id, 'mission_id' => $entretien->mission_id, 'service_id' => $entretien->service_id],
        );
    }

    public function documentUploaded(?User $actor, MissionDocument $document, Request $request): AuditLog
    {
        return $this->log(
            'document_uploaded',
            'missions',
            'Document mission — '.$document->original_name,
            $actor,
            $request,
            [
                'mission_document_id' => $document->id,
                'mission_id' => $document->mission_id,
                'service_id' => $document->service_id,
            ],
        );
    }

    public function documentDeleted(?User $actor, MissionDocument $document, Request $request): AuditLog
    {
        return $this->log(
            'document_deleted',
            'missions',
            'Suppression document — '.$document->original_name,
            $actor,
            $request,
            [
                'mission_document_id' => $document->id,
                'mission_id' => $document->mission_id,
                'service_id' => $document->service_id,
            ],
        );
    }

    public function consolidationGenerated(?User $actor, DepartmentAuditConsolidation $row, Request $request): AuditLog
    {
        return $this->log(
            'consolidation_generated',
            'missions',
            'Consolidation départementale — mission #'.$row->mission_id,
            $actor,
            $request,
            ['department_audit_consolidation_id' => $row->id, 'mission_id' => $row->mission_id],
        );
    }
}
