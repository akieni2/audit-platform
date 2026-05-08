<?php

namespace App\Models;

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

public function reponses()
{
    return $this->hasMany(Reponse::class);
}

}