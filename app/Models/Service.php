<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'mission_id',
        'code',
        'nom',
        'responsable',
        'description',
        'chef_service_user_id',
        'chef_service_nom',
        'chef_service_fonction',
        'chef_service_email',
        'chef_service_telephone',
        'service_type',
        'service_scope',
        'active',
        'observations',
        'audit_priority',
        'risk_level',
        'audit_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function chefServiceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chef_service_user_id');
    }

    public function entretiens(): HasMany
    {
        return $this->hasMany(Entretien::class, 'service_id');
    }

    public function identifiedRisks(): HasMany
    {
        return $this->hasMany(IdentifiedRisk::class, 'service_id');
    }

    public function missionDocuments(): HasMany
    {
        return $this->hasMany(MissionDocument::class, 'service_id');
    }

    /** Libellé responsable : utilisateur IAM ou champs libres. */
    public function responsableDisplay(): string
    {
        if ($this->relationLoaded('chefServiceUser') && $this->chefServiceUser) {
            return $this->chefServiceUser->displayName();
        }
        if ($this->chef_service_nom) {
            return (string) $this->chef_service_nom;
        }

        return (string) ($this->responsable ?? '—');
    }
}
