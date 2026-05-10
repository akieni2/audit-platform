<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Actif extends Model
{

    protected $fillable = [
        'processus_id',
        'nom',
        'type',
        'description'
    ];

    /*
    |-----------------------------------------
    | RELATIONS
    |-----------------------------------------
    */

    public function processus()
    {
        return $this->belongsTo(Processus::class);
    }

    /**
     * @param  Builder<Actif>  $query
     * @return Builder<Actif>
     */
    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        return $query->whereHas(
            'processus.mission',
            fn (Builder $mq) => $mq->visibleToUser($user)
        );
    }

    public function risques()
    {
        return $this->hasMany(Risque::class);
    }

}
