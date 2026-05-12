<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Entretien extends Model
{
    protected $fillable = [
        'mission_id',
        'service_id',
        'questionnaire_template_id',
        'responsable_nom',
        'role',
        'chef_hierarchique',
        'auditeur',
        'date_entretien',
        'email',
        'telephone',
        'notes',
    ];

/*
|-----------------------------------
| RELATIONS
|-----------------------------------
*/

public function mission()
{
    return $this->belongsTo(Mission::class);
}

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function questionnaireTemplate()
    {
        return $this->belongsTo(QuestionnaireTemplate::class, 'questionnaire_template_id');
    }

    /** Réponses au questionnaire dynamique (Phase 1.5). */
    public function questionnaireResponses()
    {
        return $this->hasMany(EntretienResponse::class);
    }

    public function identifiedRisks()
    {
        return $this->hasMany(IdentifiedRisk::class);
    }

/**
 * @param  Builder<Entretien>  $query
 * @return Builder<Entretien>
 */
public function scopeVisibleToUser(Builder $query, User $user): Builder
{
    return $query->where(function (Builder $q) use ($user) {
        $q->whereHas('mission', fn (Builder $mq) => $mq->visibleToUser($user))
            ->orWhereHas(
                'service.mission',
                fn (Builder $mq) => $mq->visibleToUser($user)
            );
    });
}

    public function reponses()
    {
        return $this->hasMany(Reponse::class);
    }
}
