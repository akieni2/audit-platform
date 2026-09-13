<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class CfdtCourse extends Model {protected $guarded=[];protected function casts():array{return ['content'=>'array','questions'=>'array','published_at'=>'datetime'];}public function enrollments(){return $this->hasMany(CfdtEnrollment::class,'course_id');}}
