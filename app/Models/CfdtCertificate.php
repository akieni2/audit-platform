<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class CfdtCertificate extends Model {protected $guarded=[];protected function casts():array{return ['issued_at'=>'datetime'];}public function enrollment(){return $this->belongsTo(CfdtEnrollment::class);}}
