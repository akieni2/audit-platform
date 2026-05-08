<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{

protected $fillable = [
'questionnaire_id',
'question',
'type',
'impact',
'probabilite'
];

}
