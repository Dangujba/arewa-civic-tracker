<?php
namespace Tests\Feature;

use App\Models\CivicCase;
use App\Models\DataGovernancePolicy;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GovernanceAndAccessibilityTest extends TestCase
{
 use RefreshDatabase;
 private function user(string $role,string $email):User {
  return User::create(['name'=>'Governance Test','email'=>$email,'password'=>'test-password',
   'role'=>$role,'is_active'=>true,'must_change_password'=>false]);
 }
 private function report(User $user):CivicCase {
  $l=Lga::create(['name'=>'Damaturu','code'=>'DAM']);
  $s=Sector::create(['name'=>'Education']);
  return CivicCase::create([
   'uuid'=>(string)Str::uuid(),'client_submission_id'=>(string)Str::uuid(),
   'payload_hash'=>str_repeat('a',64),'reference'=>'ACT-YOB-'.Str::random(8),
   'reporter_id'=>$user->id,'type'=>'public_project','title'=>'Classroom rehabilitation',
   'description'=>'An assessment of a damaged classroom.',
   'lga_id'=>$l->id,'community'=>'Damaturu','sector_id'=>$s->id,
   'observation_date'=>today()->toDateString(),
   'progress_status'=>'ongoing','verification_status'=>'submitted'
  ]);
 }
 public function test_admin_can_save_draft_and_record_documented_approval_without_deleting_reports():void {
  $admin=$this->user('admin','admin@example.test');
  $case=$this->report($admin);
  $this->actingAs($admin)->get(route('admin.governance.index'))->assertOk()
   ->assertSee('Retention Review Preview')->assertSee('No deletion operation');
  $this->post(route('admin.governance.save'),[
   'case_retention_days'=>3650,'evidence_retention_days'=>3650,
   'audit_retention_days'=>3650,'notes'=>'Policy pending RAWYOD formal authorization'
  ])->assertRedirect(route('admin.governance.index'));
  $this->assertDatabaseHas('data_governance_policies',['state'=>'draft','case_retention_days'=>3650]);
  $this->post(route('admin.governance.approve'),[
    'approval_reference'=>'RAWYOD-BOARD-2026-001','confirm_no_purge'=>'1'
  ])->assertRedirect(route('admin.governance.index'));
  $this->assertSame('approved',DataGovernancePolicy::firstOrFail()->state);
  $this->assertDatabaseHas('civic_cases',['id'=>$case->id]);
  $this->post(route('admin.governance.save'),['case_retention_days'=>1825])
    ->assertRedirect(route('admin.governance.index'));
  $this->assertSame('draft',DataGovernancePolicy::firstOrFail()->state);
  $this->assertDatabaseHas('civic_cases',['id'=>$case->id]);
  $this->assertDatabaseHas('admin_activity_logs',['action'=>'governance.policy_approved']);
 }
 public function test_focal_cannot_view_or_change_governance():void {
  $focal=$this->user('focal','focal@example.test');
  $this->actingAs($focal)->get(route('admin.governance.index'))->assertForbidden();
  $this->post(route('admin.governance.save'),['case_retention_days'=>1000])->assertForbidden();
  $this->assertSame(0,DataGovernancePolicy::count());
 }
 public function test_admin_can_apply_and_release_audited_legal_hold():void {
  $admin=$this->user('admin','hold@example.test');$case=$this->report($admin);
  $this->actingAs($admin)->post(route('admin.governance.hold',$case),[
   'hold'=>'1','reason'=>'Ongoing review requires evidence preservation'
  ])->assertRedirect(route('cases.show',$case));
  $this->assertTrue((bool)$case->fresh()->legal_hold);
  $this->assertDatabaseHas('admin_activity_logs',['action'=>'governance.legal_hold_set','subject_id'=>$case->id]);
  $this->post(route('admin.governance.hold',$case),[
   'hold'=>'0','reason'=>'Review concluded with written authorization'
  ])->assertRedirect(route('cases.show',$case));
  $this->assertFalse((bool)$case->fresh()->legal_hold);
  $this->assertDatabaseHas('admin_activity_logs',['action'=>'governance.legal_hold_released','subject_id'=>$case->id]);
 }
 public function test_offsite_command_refuses_unconfigured_storage():void {
  config()->set('act.offsite_backup_dir',null);
  $this->artisan('act:copy-backup-offsite')->assertExitCode(1);
 }
 public function test_page_has_skip_link_and_accessible_landmark():void {
  $admin=$this->user('admin','a11y@example.test');
  $this->actingAs($admin)->get(route('admin.governance.index'))->assertOk()
   ->assertSee('href="#main-content"',false)
   ->assertSee('id="main-content"',false)
   ->assertSee('act-accessibility.css');
 }
}
