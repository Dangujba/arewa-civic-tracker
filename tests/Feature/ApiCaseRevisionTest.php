<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\CivicCase;
use App\Models\CaseRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiCaseRevisionTest extends TestCase
{
 use RefreshDatabase;
 private function focal(string $email):User {
  return User::create(['name'=>'Field Reporter','email'=>$email,'password'=>'password',
   'role'=>'focal','is_active'=>true,'must_change_password'=>false]);
 }
 private function report(User $user):CivicCase {
  $l=Lga::firstOrCreate(['code'=>'DAM'],['name'=>'Damaturu']);
  $s=Sector::firstOrCreate(['name'=>'Education']);
  $user->assignedLgas()->syncWithoutDetaching([$l->id]);
  return CivicCase::create([
   'uuid'=>(string)Str::uuid(),'client_submission_id'=>(string)Str::uuid(),
   'payload_hash'=>str_repeat('a',64),'reference'=>'ACT-YOB-'.Str::random(9),
   'reporter_id'=>$user->id,'type'=>'public_project','title'=>'Original classroom report',
   'description'=>'Original report with valid description.','lga_id'=>$l->id,
   'community'=>'Central Damaturu','sector_id'=>$s->id,'observation_date'=>today()->toDateString(),
   'progress_status'=>'ongoing','verification_status'=>'submitted']);
 }
 private function payload(CivicCase $case):array {
  return [
   'version'=>0,'reason'=>'Correcting field observation after review',
   'type'=>'public_project','title'=>'Original classroom report',
   'description'=>'Updated classroom report with corrected details.',
   'lga_id'=>$case->lga_id,'community'=>$case->community,'sector_id'=>$case->sector_id,
   'observation_date'=>$case->observation_date->format('Y-m-d'),
   'observations'=>'Confirmed additional details','duty_bearers'=>'',
   'latitude'=>null,'longitude'=>null
  ];
 }
 public function test_native_api_can_read_editable_fields_and_post_audited_revision():void {
  $u=$this->focal('report-owner@example.test');$c=$this->report($u);
  Sanctum::actingAs($u,['field:report']);
  $this->getJson('/api/v1/reports/'.$c->id)->assertOk()
   ->assertJsonPath('editable',true)->assertJsonPath('version',0)
   ->assertJsonPath('fields.type','public_project')
   ->assertJsonPath('fields.lga_id',$c->lga_id);
  $this->putJson('/api/v1/reports/'.$c->id,$this->payload($c))->assertOk()
   ->assertJsonPath('version',1);
  $this->assertSame('Updated classroom report with corrected details.',$c->fresh()->description);
  $this->assertSame(1,CaseRevision::count());
  $this->assertDatabaseHas('case_events',['case_id'=>$c->id,'event_type'=>'report_revised']);
  $this->assertDatabaseHas('admin_activity_logs',['subject_id'=>$c->id,'action'=>'case.revised']);
  $this->getJson('/api/v1/reports/'.$c->id)->assertJsonPath('version',1);
  $this->putJson('/api/v1/reports/'.$c->id,$this->payload($c))->assertStatus(422)
   ->assertJsonValidationErrors('version');
 }
 public function test_native_api_rejects_other_users_and_locked_cases():void {
  $owner=$this->focal('original-owner@example.test');$c=$this->report($owner);
  $other=$this->focal('unrelated@example.test');
  $other->assignedLgas()->syncWithoutDetaching([$c->lga_id]);
  Sanctum::actingAs($other,['field:report']);
  $this->putJson('/api/v1/reports/'.$c->id,$this->payload($c))->assertForbidden();
  Sanctum::actingAs($owner,['field:report']);
  $c->update(['verification_status'=>'approved']);
  $this->putJson('/api/v1/reports/'.$c->id,$this->payload($c))->assertForbidden();
  $this->getJson('/api/v1/reports/'.$c->id)->assertJsonPath('editable',false);
 }
 public function test_native_revision_requires_correct_token_scope_and_reason():void {
  $u=$this->focal('scope@example.test');$c=$this->report($u);
  Sanctum::actingAs($u,['other:scope']);
  $this->putJson('/api/v1/reports/'.$c->id,$this->payload($c))->assertForbidden();
  Sanctum::actingAs($u,['field:report']);
  $invalid=$this->payload($c);$invalid['reason']='';
  $this->putJson('/api/v1/reports/'.$c->id,$invalid)->assertStatus(422)
   ->assertJsonValidationErrors('reason');
 }
}
