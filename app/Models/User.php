<?php

namespace App\Models;

use Closure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'prenom',
        'telephone',
        'matricule',
        'date_naissance',
        'fonction',
        'role',
        'department_id',
        'role_id',
        'active',
        'position',
        'profile_photo',
        'last_login_at',
        'password_changed_at',
        'failed_login_attempts',
        'locked_until',
        'mfa_enabled',
        'mfa_recovery_codes',
        'must_change_password',
        'password_expires_at',
        'approval_status',
        'approved_at',
        'approved_by',
        'registration_requested_department_id',
        'deleted_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'locked_until' => 'datetime',
            'mfa_enabled' => 'boolean',
            'must_change_password' => 'boolean',
            'password_expires_at' => 'datetime',
            'approved_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /** Administrateur ayant effectué la suppression IAM (soft delete). */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by')->withTrashed();
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isPendingApproval(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isEnrollmentRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function registrationRequestedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'registration_requested_department_id');
    }

    /**
     * Comptes Super Admin institutionnels actifs (notifications enrôlement).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function scopeInstitutionalSuperAdmins($query)
    {
        return $query
            ->where('approval_status', 'approved')
            ->where('active', true)
            ->whereHas('institutionalRole', fn ($q) => $q->where('slug', 'super_admin'));
    }

    public function missions()
    {
        return $this->hasMany(Mission::class, 'auditeur_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** Superviseur institutionnel du département (Department::supervisor_user_id). */
    public function isDepartmentSupervisorOf(?int $departmentId): bool
    {
        if ($departmentId === null) {
            return false;
        }

        return (int) Department::query()->whereKey($departmentId)->value('supervisor_user_id') === (int) $this->id;
    }

    /**
     * Propriétaire institutionnel de mission ou pouvoir national (inspection / super_admin / supervise_global).
     */
    public function canGovernMissionInstitutionally(Mission $mission): bool
    {
        if ($this->canSuperviseAllDepartments()) {
            return true;
        }

        return $this->isDepartmentSupervisorOf($mission->department_id !== null ? (int) $mission->department_id : null);
    }

    /**
     * Membre opérationnel autorisé à enrichir le contenu non stratégique (hors délais / département / statut).
     */
    public function isMissionOperationalContributor(Mission $mission): bool
    {
        $mission->loadMissing('missionTeamMembers');

        $allowed = [
            MissionTeamMember::ROLE_CHEF_MISSION,
            MissionTeamMember::ROLE_INSPECTEUR_VERIFICATEUR,
            MissionTeamMember::ROLE_INSPECTEUR_VERIFICATEUR_ADJOINT,
            MissionTeamMember::ROLE_AGENT,
            MissionTeamMember::ROLE_ASSISTANT,
        ];

        foreach ($mission->missionTeamMembers as $row) {
            if ((int) $row->user_id !== (int) $this->id) {
                continue;
            }
            if (in_array($row->mission_role, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    /** Rôle institutionnel DGCPT (table roles). */
    public function institutionalRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /** Affichage « Prénom Nom » lorsque les deux champs sont renseignés. */
    public function displayName(): string
    {
        $parts = array_filter([$this->prenom, $this->name]);

        return $parts !== []
            ? implode(' ', $parts)
            : (string) ($this->name ?? '');
    }

    /** Précharge les relations nécessaires aux décisions IAM / RBAC. */
    public function loadIamRelations(): static
    {
        return $this->loadMissing([
            'department',
            'institutionalRole',
            'institutionalRole.permissions',
        ]);
    }

    /**
     * Cache booléen par cycle de requête (Request attributes) pour limiter les requêtes répétées.
     */
    protected function iamBool(string $key, Closure $resolver): bool
    {
        if (app()->runningInConsole() || ! app()->bound('request')) {
            return $resolver();
        }

        $request = request();
        $attrKey = '_iam_'.$this->getKey().'_'.$key;
        if ($request->attributes->has($attrKey)) {
            return (bool) $request->attributes->get($attrKey);
        }

        $value = (bool) $resolver();
        $request->attributes->set($attrKey, $value);

        return $value;
    }

    /**
     * @template T of string
     *
     * @param  Closure():T  $resolver
     * @return T
     */
    protected function iamString(string $key, Closure $resolver): string
    {
        if (app()->runningInConsole() || ! app()->bound('request')) {
            return $resolver();
        }

        $request = request();
        $attrKey = '_iam_str_'.$this->getKey().'_'.$key;
        if ($request->attributes->has($attrKey)) {
            /** @var T */
            return $request->attributes->get($attrKey);
        }

        $value = $resolver();
        $request->attributes->set($attrKey, $value);

        return $value;
    }

    protected function isLegacyAdminRole(): bool
    {
        return strtolower((string) ($this->role ?? '')) === 'admin';
    }

    public function hasPermission(string $slug): bool
    {
        $this->loadIamRelations();

        if ($this->isLegacyAdminRole()) {
            return true;
        }

        $role = $this->institutionalRole;
        if ($role === null) {
            return false;
        }

        if ($role->slug === 'super_admin') {
            return true;
        }

        return $role->permissions->contains(fn (Permission $p) => $p->slug === $slug);
    }

    /** Supervision nationale : tous départements / missions / risques. */
    public function canSuperviseAllDepartments(): bool
    {
        return $this->iamBool('supervise_all', function () {
            $this->loadIamRelations();

            if ($this->isLegacyAdminRole()) {
                return true;
            }

            $slug = $this->institutionalRole?->slug;

            return $slug === 'inspecteur_services'
                || $slug === 'super_admin'
                || $this->hasPermission('supervise_global');
        });
    }

    /** @deprecated Utiliser {@see canSuperviseAllDepartments()} */
    public function canViewAllInstitutionalData(): bool
    {
        return $this->canSuperviseAllDepartments();
    }

    /** Supervision de tout un pôle (missions du département). */
    public function canSuperviseEntirePole(): bool
    {
        if ($this->canSuperviseAllDepartments()) {
            return true;
        }

        $this->loadIamRelations();

        return $this->institutionalRole?->slug === 'inspecteur_adjoint';
    }

    protected function isAdminInstitutional(): bool
    {
        $this->loadIamRelations();

        if ($this->isLegacyAdminRole()) {
            return true;
        }

        $slug = $this->institutionalRole?->slug;

        return $slug === 'admin' || $slug === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->iamBool('is_admin', fn () => $this->isAdminInstitutional());
    }

    public function isAuditeur(): bool
    {
        return $this->role === 'auditeur';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isRiskManager(): bool
    {
        $this->loadIamRelations();

        return $this->role === 'risk_manager'
            || $this->institutionalRole?->slug === 'risk_manager';
    }

    /** Compte système configuré (email institutionnel Super Admin). */
    public function isProtectedSystemAdministrator(): bool
    {
        $configured = strtolower((string) config('dgcpt.super_admin_email', 'admin@dgcpt.ga'));

        return strtolower((string) $this->email) === $configured;
    }

    public function isInstitutionalSuperAdmin(): bool
    {
        $this->loadIamRelations();

        return $this->institutionalRole?->slug === 'super_admin';
    }

    /** Menu latéral Administration / IAM — Gate « manageUsers ». */
    public function canAccessAdministrationMenu(): bool
    {
        return $this->iamBool('admin_menu', function () {
            $this->loadIamRelations();

            if ($this->isLegacyAdminRole()) {
                return true;
            }

            $slug = $this->institutionalRole?->slug;
            if ($slug === 'super_admin' || $slug === 'admin') {
                return true;
            }

            return $this->hasPermission('manage_users');
        });
    }

    /** Tableau de bord exécutif / stratégique — Gate « viewExecutiveDashboard ». */
    public function canViewExecutiveDashboard(): bool
    {
        return $this->iamBool('exec_dashboard', function () {
            $this->loadIamRelations();

            return $this->isAdminInstitutional()
                || $this->institutionalRole?->slug === 'inspecteur_services'
                || $this->institutionalRole?->slug === 'copri'
                || $this->hasPermission('supervise')
                || $this->hasPermission('supervise_global');
        });
    }

    /**
     * Mode de navigation latéral : gouvernance DGCPT / COPRI (parallèle hiérarchie / workflow).
     *
     * @return 'technical_admin'|'copri'|'inspection'|'department'
     */
    public function institutionalNavMode(): string
    {
        return $this->iamString('nav_mode', function (): string {
            $this->loadIamRelations();

            if ($this->isLegacyAdminRole()) {
                return 'technical_admin';
            }

            $slug = $this->institutionalRole?->slug;
            if ($slug === 'super_admin' || $slug === 'admin') {
                return 'technical_admin';
            }

            if ($slug === 'copri') {
                return 'copri';
            }

            if ($slug === 'inspecteur_services' || $slug === 'inspecteur_adjoint' || $this->hasPermission('supervise_global')) {
                return 'inspection';
            }

            return 'department';
        });
    }

    /** Gestion des comptes à l’échelle d’un pôle (superviseurs). */
    public function canManageDepartmentUsers(): bool
    {
        return $this->iamBool('manage_dept_users', function () {
            $this->loadIamRelations();

            return $this->hasPermission('supervise_department')
                || ($this->hasPermission('manage_users') && ! $this->canSuperviseAllDepartments());
        });
    }

    /** Journal sécurité / audits IAM — Gate « viewSecurityAuditLog ». */
    public function canAccessSecurityLogs(): bool
    {
        return $this->iamBool('security_logs', fn () => $this->canAccessAdministrationMenu());
    }

    /** Annuaire et structure des pôles — Gate « manageDepartments ». */
    public function canManageDepartments(): bool
    {
        return $this->iamBool('manage_departments', function () {
            $this->loadIamRelations();

            if ($this->isLegacyAdminRole()) {
                return true;
            }

            $slug = $this->institutionalRole?->slug;
            if ($slug === 'super_admin' || $slug === 'admin') {
                return true;
            }

            return $this->hasPermission('manage_departments');
        });
    }

    /** Bibliothèque de questionnaires d’audit (référentiels dynamiques). */
    public function canManageQuestionnaireLibrary(): bool
    {
        return $this->iamBool('manage_questionnaire_library', function () {
            $this->loadIamRelations();

            if ($this->canSuperviseAllDepartments()) {
                return true;
            }

            if ($this->department_id !== null && $this->isDepartmentSupervisorOf((int) $this->department_id)) {
                return true;
            }

            return $this->hasPermission('manage_questionnaire_templates');
        });
    }

    /** Création / modification de missions selon permissions métier. */
    public function canManageMissions(): bool
    {
        return $this->iamBool('manage_missions', function () {
            $this->loadIamRelations();

            if ($this->isLegacyAdminRole()) {
                return true;
            }

            $slug = $this->institutionalRole?->slug;
            if (in_array($slug, ['super_admin', 'inspecteur_services', 'admin'], true)) {
                return true;
            }

            return $this->hasPermission('create_mission')
                || $this->hasPermission('update_mission')
                || $this->hasPermission('delete_mission')
                || $this->hasPermission('validate_mission');
        });
    }

    /** Risques institutionnels (création, transfert, criticité). */
    public function canManageRisks(): bool
    {
        return $this->iamBool('manage_risks', function () {
            $this->loadIamRelations();

            if ($this->isLegacyAdminRole()) {
                return true;
            }

            if ($this->institutionalRole?->slug === 'risk_manager') {
                return true;
            }

            return $this->hasPermission('create_risk')
                || $this->hasPermission('transfer_risk');
        });
    }
}
