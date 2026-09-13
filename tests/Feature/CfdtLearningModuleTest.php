<?php
namespace Tests\Feature;
use App\Models\{CfdtCertificate,CfdtCourse,CfdtEnrollment,Role,User}; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class CfdtLearningModuleTest extends TestCase {use RefreshDatabase;
 private function user(string $slug,string $cfdt):User{$role=Role::firstOrCreate(['slug'=>$slug],['name'=>$slug,'hierarchy_level'=>100,'active'=>true]);return User::factory()->create(['role_id'=>$role->id,'cfdt_role'=>$cfdt,'active'=>true,'approval_status'=>'approved']);}
 public function test_trainer_creates_questions_validator_publishes_and_learner_earns_certificate():void{
  $trainer=$this->user('auditeur','trainer');$validator=$this->user('auditeur','validator');$learner=$this->user('auditeur','learner');
  $this->actingAs($trainer)->post(route('cfdt.store'),['code'=>'CFDT-01','title'=>'Comptabilité publique','pass_mark'=>70,'max_attempts'=>3])->assertRedirect();$course=CfdtCourse::firstOrFail();
  $this->actingAs($trainer)->post(route('cfdt.questions.store',$course),['text'=>'Quel principe garantit la traçabilité ?','type'=>'single','options'=>['Journalisation','Suppression','Anonymat'],'correct'=>[0],'points'=>2])->assertRedirect();
  $this->actingAs($validator)->patch(route('cfdt.publish',$course))->assertRedirect();
  $this->actingAs($trainer)->post(route('cfdt.enroll',$course),['user_ids'=>[$learner->id]])->assertRedirect();$e=CfdtEnrollment::firstOrFail();$qid=CfdtCourse::first()->questions[0]['id'];
  $this->actingAs($learner)->post(route('cfdt.submit',$course),['answers'=>[$qid=>[0]]])->assertRedirect();
  $this->assertDatabaseHas('cfdt_attempts',['enrollment_id'=>$e->id,'passed'=>true]);$this->assertDatabaseHas('cfdt_certificates',['enrollment_id'=>$e->id]);
 }
 public function test_unprivileged_user_is_forbidden():void{$user=$this->user('auditeur','');$this->actingAs($user)->get(route('cfdt.index'))->assertForbidden();}
 public function test_super_admin_can_grant_and_revoke_cfdt_access():void{
  $super=$this->user('super_admin','');$agent=$this->user('auditeur','');
  $this->actingAs($super)->patch(route('cfdt.access.update',$agent),['cfdt_role'=>'learner'])->assertRedirect();
  $this->assertDatabaseHas('users',['id'=>$agent->id,'cfdt_role'=>'learner']);
  $this->actingAs($super)->patch(route('cfdt.access.update',$agent),['cfdt_role'=>''])->assertRedirect();
  $this->assertDatabaseHas('users',['id'=>$agent->id,'cfdt_role'=>null]);
 }
 public function test_learner_cannot_download_another_learners_certificate():void{
  $owner=$this->user('auditeur','learner');$intruder=$this->user('auditeur','learner');
  $course=CfdtCourse::create(['code'=>'SEC-01','title'=>'Sécurité','questions'=>[],'content'=>[],'created_by'=>$owner->id]);
  $enrollment=CfdtEnrollment::create(['course_id'=>$course->id,'user_id'=>$owner->id,'assigned_by'=>$owner->id,'assigned_at'=>now()]);
  $certificate=CfdtCertificate::create(['enrollment_id'=>$enrollment->id,'verification_token'=>(string)\Illuminate\Support\Str::uuid(),'number'=>'CFDT-TEST-1','score'=>100,'issued_at'=>now(),'signature_hash'=>'test']);
  $this->actingAs($intruder)->get(route('cfdt.certificate',$certificate))->assertForbidden();
 }
}
