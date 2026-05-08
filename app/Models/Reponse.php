<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reponse extends Model
{

protected $fillable = [

'entretien_id',
'question_id',
'reponse',
'observation'

];

public function question()
{
return $this->belongsTo(Question::class);
}

public function entretien()
{
return $this->belongsTo(Entretien::class);
}

}
