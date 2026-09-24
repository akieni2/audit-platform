<?php
namespace Tests\Feature;
use App\Models\{CfdtAttempt,CfdtCertificate,CfdtCourse,CfdtEnrollment,Department,Role,User}; use App\Notifications\Cfdt\CfdtTestAssignedNotification; use Illuminate\Foundation\Testing\RefreshDatabase; use Illuminate\Support\Facades\Notification; use Tests\TestCase;
class CfdtLearningModuleTest extends TestCase {use RefreshDatabase;
 private function user(string $slug,string $cfdt):User{$role=Role::firstOrCreate(['slug'=>$slug],['name'=>$slug,'hierarchy_level'=>100,'active'=>true]);return User::factory()->create(['role_id'=>$role->id,'cfdt_role'=>$cfdt,'active'=>true,'approval_status'=>'approved']);}
 public function test_trainer_creates_questions_validator_publishes_and_learner_earns_certificate():void{
  $trainer=$this->user('auditeur','trainer');$validator=$this->user('auditeur','validator');$learner=$this->user('auditeur','learner');
  $this->actingAs($trainer)->post(route('cfdt.store'),['code'=>'CFDT-01','title'=>'Comptabilité publique','pass_mark'=>70,'max_attempts'=>3])->assertRedirect();$course=CfdtCourse::firstOrFail();
  $this->actingAs($trainer)->post(route('cfdt.questions.store',$course),['text'=>'Quel principe garantit la traçabilité ?','type'=>'single','options'=>['Journalisation','Suppression','Anonymat'],'correct'=>[0],'points'=>2])->assertRedirect();
  $this->actingAs($trainer)->patch(route('cfdt.submit-review',$course))->assertRedirect();
  $this->actingAs($validator)->patch(route('cfdt.review',$course),['decision'=>'approve'])->assertRedirect();
  $this->actingAs($trainer)->post(route('cfdt.enroll',$course),['user_ids'=>[$learner->id],'expires_at'=>now()->addWeek()->format('Y-m-d H:i:s')])->assertRedirect();$e=CfdtEnrollment::firstOrFail();$qid=CfdtCourse::first()->questions[0]['id'];
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
 public function test_cfdt_administrator_can_create_trainer_but_not_another_administrator():void{
  Notification::fake();$admin=$this->user('auditeur','administrator');$department=Department::create(['name'=>'Pôle formation','code'=>'PF','type'=>'pole','active'=>true]);
  $payload=['name'=>'FORMATEUR','prenom'=>'Alice','email'=>'alice.formateur@example.test','department_id'=>$department->id,'fonction'=>'Formatrice','role'=>'agent_operationnel','cfdt_role'=>'trainer'];
  $this->actingAs($admin)->post(route('cfdt.access.store'),$payload)->assertRedirect();
  $this->assertDatabaseHas('users',['email'=>'alice.formateur@example.test','cfdt_role'=>'trainer','department_id'=>$department->id]);
  $this->actingAs($admin)->post(route('cfdt.access.store'),[...$payload,'email'=>'admin.cfdt@example.test','cfdt_role'=>'administrator'])->assertSessionHasErrors('cfdt_role');
 }
 public function test_trainer_assigns_test_to_department_descendants_and_role_with_expiry():void{
  Notification::fake();$trainer=$this->user('auditeur','trainer');$parent=Department::create(['name'=>'Direction test','code'=>'DT','type'=>'direction','active'=>true]);$child=Department::create(['name'=>'Service test','code'=>'ST','type'=>'service','active'=>true,'parent_department_id'=>$parent->id]);$learner=$this->user('auditeur','learner');$learner->update(['department_id'=>$child->id,'role'=>'inspecteur_verificateur']);$course=CfdtCourse::create(['code'=>'CIBLE-01','title'=>'Test ciblé','questions'=>[],'content'=>[],'created_by'=>$trainer->id,'status'=>'published','published_at'=>now()]);
  $this->actingAs($trainer)->post(route('cfdt.enroll',$course),['department_ids'=>[$parent->id],'include_descendants'=>1,'role_categories'=>['inspecteur_verificateur'],'expires_at'=>now()->addDays(3)->format('Y-m-d H:i:s')])->assertRedirect();
  $enrollment=CfdtEnrollment::where('user_id',$learner->id)->firstOrFail();$this->assertNotNull($enrollment->invitation_token);$this->assertNotNull($enrollment->expires_at);Notification::assertSentTo($learner,CfdtTestAssignedNotification::class);
 }
 public function test_expired_assignment_cannot_be_opened_or_submitted():void{
  $learner=$this->user('auditeur','learner');$course=CfdtCourse::create(['code'=>'EXP-01','title'=>'Test expiré','questions'=>[],'content'=>[],'created_by'=>$learner->id]);$enrollment=CfdtEnrollment::create(['course_id'=>$course->id,'user_id'=>$learner->id,'assigned_by'=>$learner->id,'assigned_at'=>now()->subDays(2),'expires_at'=>now()->subDay(),'invitation_token'=>(string)\Illuminate\Support\Str::uuid()]);
  $this->actingAs($learner)->get(route('cfdt.invitation',$enrollment->invitation_token))->assertForbidden();$this->actingAs($learner)->get(route('cfdt.attempt',$course))->assertForbidden();
 }
 public function test_qcm_attempt_keeps_question_titles_inside_responsive_cards():void{
  $learner=$this->user('auditeur','learner');$course=CfdtCourse::create(['code'=>'LAYOUT-01','title'=>'Audit des systèmes','status'=>'published','published_at'=>now(),'questions'=>[['id'=>'question-1','text'=>'Qui est le premier président gabonais ?','type'=>'single','options'=>['Léon Mba','Thomas Sankara'],'correct'=>[0],'points'=>1]],'content'=>[],'created_by'=>$learner->id]);CfdtEnrollment::create(['course_id'=>$course->id,'user_id'=>$learner->id,'assigned_by'=>$learner->id,'assigned_at'=>now(),'expires_at'=>now()->addDay()]);
  $this->actingAs($learner)->get(route('cfdt.attempt',$course))->assertOk()->assertSee('Qui est le premier président gabonais ?')->assertSee('aria-labelledby="question-question-1"',false)->assertDontSee('<legend',false);
 }
 public function test_browser_string_answers_are_scored_and_detailed_correction_is_displayed():void{
  $learner=$this->user('auditeur','learner');$course=CfdtCourse::create(['code'=>'RESULT-01','title'=>'Test avec corrigé','status'=>'published','published_at'=>now(),'pass_mark'=>50,'max_attempts'=>2,'questions'=>[['id'=>'q1','text'=>'Capitale du Gabon ?','type'=>'single','options'=>['Libreville','Oyem'],'correct'=>[0],'points'=>2,'explanation'=>'Libreville est la capitale.'],['id'=>'q2','text'=>'Réponse volontairement fausse ?','type'=>'single','options'=>['Bonne','Mauvaise'],'correct'=>[0],'points'=>2]],'content'=>[],'created_by'=>$learner->id]);CfdtEnrollment::create(['course_id'=>$course->id,'user_id'=>$learner->id,'assigned_by'=>$learner->id,'assigned_at'=>now(),'expires_at'=>now()->addDay()]);
  $response=$this->actingAs($learner)->post(route('cfdt.submit',$course),['answers'=>['q1'=>['0'],'q2'=>['1']]]);$attempt=CfdtAttempt::firstOrFail();$response->assertRedirect(route('cfdt.result',$attempt));$this->assertEquals(50.0,$attempt->percentage);
  $this->actingAs($learner)->get(route('cfdt.result',$attempt))->assertOk()->assertSee('Corrigé de l’évaluation')->assertSee('Correct')->assertSee('Incorrect')->assertSee('Votre réponse')->assertSee('Bonne réponse')->assertSee('Libreville est la capitale.');
 }
 public function test_learner_cannot_view_another_learners_correction():void{
  $owner=$this->user('auditeur','learner');$intruder=$this->user('auditeur','learner');$course=CfdtCourse::create(['code'=>'RESULT-SEC','title'=>'Corrigé privé','status'=>'published','questions'=>[],'content'=>[],'created_by'=>$owner->id]);$enrollment=CfdtEnrollment::create(['course_id'=>$course->id,'user_id'=>$owner->id,'assigned_by'=>$owner->id,'assigned_at'=>now()]);$attempt=CfdtAttempt::create(['enrollment_id'=>$enrollment->id,'question_snapshot'=>[],'answers'=>[],'score'=>0,'total'=>0,'percentage'=>0,'passed'=>false,'started_at'=>now(),'submitted_at'=>now()]);
  $this->actingAs($intruder)->get(route('cfdt.result',$attempt))->assertForbidden();
 }
}
