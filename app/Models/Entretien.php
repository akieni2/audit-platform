<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Entretien extends Model
{
protected $fillable = [

    'mission_id',
    'service_id',
    'responsable_nom',
    'role',
    'chef_hierarchique',
    'auditeur',
    'date_entretien',
    'email',
    'telephone',
    'notes'

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