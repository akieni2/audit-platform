<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class CfdtCourse extends Model {protected $guarded=[];protected function casts():array{return ['content'=>'array','questions'=>'array','published_at'=>'datetime','submitted_at'=>'datetime','reviewed_at'=>'datetime'];}public function enrollments(){return $this->hasMany(CfdtEnrollment::class,'course_id');}public function creator(){return $this->belongsTo(User::class,'created_by');}public function validator(){return $this->belongsTo(User::class,'validated_by');}}
