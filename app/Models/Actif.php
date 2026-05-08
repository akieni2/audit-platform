<?php

namespace App\Models;

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

    public function risques()
    {
        return $this->hasMany(Risque::class);
    }

}
