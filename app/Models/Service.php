<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{

    protected $fillable = [
        'mission_id',
        'nom',
        'description'
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

}
