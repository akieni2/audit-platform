<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class CfdtAttempt extends Model {protected $guarded=[];protected function casts():array{return ['question_snapshot'=>'array','answers'=>'array','passed'=>'boolean','started_at'=>'datetime','submitted_at'=>'datetime'];}public function enrollment(){return $this->belongsTo(CfdtEnrollment::class);}}
