<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Controle extends Model
{
protected $fillable = [
    'risque_id',
    'description',
    'type',
    'efficacite',
    'commentaire'
];
}
