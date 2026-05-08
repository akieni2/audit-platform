<?php

namespace App\Models;

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

    public function actifs()
    {
        return $this->hasMany(Actif::class);
    }
}
