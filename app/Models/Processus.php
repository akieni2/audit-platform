<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Processus extends Model
{
    protected $table = 'processus';

    protected $fillable = [
        'mission_id',
        'nom',
        'description'
    ];

    /*
    |--------------------------------------------------
    | RELATIONS
    |--------------------------------------------------
    */

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    /**
     * @param  Builder<Processus>  $query
     * @return Builder<Processus>
     */
    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('mission', fn (Builder $mq) => $mq->visibleToUser($user));
    }

    public function actifs()
    {
        return $this->hasMany(Actif::class);
    }
}
