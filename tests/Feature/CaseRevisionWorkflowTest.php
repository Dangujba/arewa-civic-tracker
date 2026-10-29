<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\CivicCase;
use App\Models\CaseRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CaseRevisionWorkflowTest extends TestCase {
 use RefreshDatabase;
 private function user(string $role,string $email):User{return User::create(['name'=>'Tester '.$role,'email'=>$email,'password'=>'abc','role'=>$role,'is_active'=>true,'must_change_password'=>false]);}
 private function report(User $owner):CivicCase{
  $l=Lga::create(['name'=>'Damaturu','code'=>'DAM']);$s=Sector::create(['name'=>'Education']);
  if($owner->role==='focal')$owner->assignedLgas()->sync([$l->id]);
  return CivicCase::create(['uuid'=>(string)\Illuminate\Support\Str::uuid(),'reference'=>'ACT-YOB-TEST-01','reporter_id'=>$owner->id,
   'client_submission_id'=>(string)\Illuminate\Support\Str::uuid(),'payload_hash'=>str_repeat('a',64),'type'=>'public_project',
   'title'=>'Original School Project','description'=>'Original school project assessment details.','lga_id'=>$l->id,'community'=>'Damaturu',
   'sector_id'=>$s->id,'observation_date'=>now()->toDateString(),'progress_status'=>'ongoing','verification_status'=>'submitted']);
 }
 private function payload(CivicCase $c,array $overrides=[]):array{return array_merge([
  'version'=>0,'reason'=>'Corrected verified description','type'=>$c->type,'title'=>$c->title,
  'description'=>'Corrected school project description and details.','lga_id'=>$c->lga_id,'community'=>$c->community,
  'sector_id'=>$c->sector_id,'observation_date'=>$c->observation_date->format('Y-m-d'),
  'observations'=>'','duty_bearers'=>'','latitude'=>'','longitude'=>''
 ],$overrides);}
 public function test_reporter_can_create_audited_revision():void{
  $u=$this->user('focal','focal@example.test');$c=$this->report($u);
  $this->actingAs($u)->get(route('cases.edit',$c))->assertOk()->assertSee('Save Revision');
  $this->put(route('cases.update',$c),$this->payload($c))->assertRedirect(route('cases.show',$c));
  $this->assertSame('Corrected school project description and details.',$c->fresh()->description);
  $r=CaseRevision::firstOrFail();$this->assertSame(1,$r->version);
  $this->assertSame('Original school project assessment details.',$r->changes['description']['from']);
  $this->assertDatabaseHas('case_events',['case_id'=>$c->id,'event_type'=>'report_revised']);
  $this->assertDatabaseHas('admin_activity_logs',['subject_id'=>$c->id,'action'=>'case.revised']);
  $this->actingAs($u)->get(route('cases.show',$c))->assertSee('Revision History')->assertSee('Corrected verified description');
 }
 public function test_approved_report_is_locked():void{
  $u=$this->user('focal','approved@example.test');$c=$this->report($u);$c->verification_status='approved';$c->save();
  $this->actingAs($u)->get(route('cases.edit',$c))->assertForbidden();
  $this->put(route('cases.update',$c),$this->payload($c))->assertForbidden();
  $this->assertSame(0,CaseRevision::count());
 }
 public function test_unrelated_user_cannot_edit():void{
  $u=$this->user('focal','owner@example.test');$c=$this->report($u);
  $other=$this->user('focal','other@example.test');
  $this->actingAs($other)->put(route('cases.update',$c),$this->payload($c))->assertForbidden();
 }
 public function test_stale_version_rejected():void{
  $u=$this->user('focal','stale@example.test');$c=$this->report($u);
  $this->actingAs($u)->put(route('cases.update',$c),$this->payload($c))->assertRedirect();
  $this->put(route('cases.update',$c),$this->payload($c,['title'=>'New changed title']))->assertSessionHasErrors('version');
  $this->assertSame(1,CaseRevision::count());
 }
 public function test_reason_is_required():void{
  $u=$this->user('focal','reason@example.test');$c=$this->report($u);
  $this->actingAs($u)->put(route('cases.update',$c),$this->payload($c,['reason'=>'']))->assertSessionHasErrors('reason');
 }
}
