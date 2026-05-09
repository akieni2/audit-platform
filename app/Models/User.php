<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
        ];
    }

    public function missions()
    {
        return $this->hasMany(Mission::class, 'auditeur_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** Rôle institutionnel DGCPT (table roles). */
    public function institutionalRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->role === 'admin') {
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

    /** Visibilité nationale (tous départements / missions / risques). */
    public function canViewAllInstitutionalData(): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        return $this->institutionalRole?->slug === 'inspecteur_services'
            || $this->institutionalRole?->slug === 'super_admin'
            || $this->hasPermission('supervise_global');
    }

    /** Supervision de tout un pôle (missions du département). */
    public function canSuperviseEntirePole(): bool
    {
        if ($this->canViewAllInstitutionalData()) {
            return true;
        }

        return $this->institutionalRole?->slug === 'inspecteur_adjoint';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin'
            || $this->institutionalRole?->slug === 'admin'
            || $this->institutionalRole?->slug === 'super_admin';
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
        return $this->institutionalRole?->slug === 'super_admin';
    }
}
