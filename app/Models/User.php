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
        'phone',
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

        return $role->permissions->contains(fn (Permission $p) => $p->slug === $slug);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin'
            || $this->institutionalRole?->slug === 'admin';
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
}
