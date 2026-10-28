<?php
namespace Tests\Feature;
use App\Models\AdminActivityLog;
use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\OrganizationSetting;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoleOperationsTest extends TestCase {
 use RefreshDatabase;
 private function staff(string $role):User {
  return User::create(['name'=>'Test '.ucfirst($role),'email'=>Str::random(9).'@example.test',
   'password'=>'Test-Strong-Secret!2026','role'=>$role,'is_active'=>true,'must_change_password'=>false]);
 }
 private function case(User $reporter,Lga $lga):CivicCase {
  $sector=Sector::firstOrCreate(['name'=>'Education']);
  return CivicCase::create(['uuid'=>(string)Str::uuid(),'client_submission_id'=>(string)Str::uuid(),
   'payload_hash'=>Str::random(64),'reference'=>'ACT-TEST-'.Str::upper(Str::random(10)),
   'reporter_id'=>$reporter->id,'type'=>'public_project','title'=>'Synthetic report for operations',
   'description'=>'Testing reviewer assignment and administrator operations.',
   'lga_id'=>$lga->id,'community'=>'QA','sector_id'=>$sector->id,
   'observation_date'=>today(),'progress_status'=>'ongoing','verification_status'=>'submitted']);
 }
 public function test_each_staff_role_gets_its_own_dashboard_and_restricted_admin_operations():void {
  $admin=$this->staff('admin');$meal=$this->staff('meal');$focal=$this->staff('focal');
  $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Administrator dashboard')->assertSee('Field coverage');
  $this->get('/admin/operations')->assertOk()->assertSee('Focal person performance')->assertSee('MEAL reviewer workload');
  $this->actingAs($meal)->get('/dashboard')->assertOk()->assertSee('MEAL review dashboard')->assertDontSee('Administrator dashboard');
  $this->get('/admin/operations')->assertForbidden();
  $this->actingAs($focal)->get('/dashboard')->assertOk()->assertSee('My field dashboard')->assertDontSee('MEAL review dashboard');
  $this->get('/admin/activity')->assertForbidden();
  $this->get('/admin/users')->assertForbidden();
 }
 public function test_admin_assigns_focal_lgas_and_server_rejects_report_outside_assigned_area():void {
  $admin=$this->staff('admin');
  $first=Lga::firstOrCreate(['name'=>'Damaturu'],['code'=>'DAM']);
  $other=Lga::firstOrCreate(['name'=>'Potiskum'],['code'=>'POT']);
  $this->actingAs($admin)->post('/admin/users',[
   'name'=>'Field staff','email'=>'field-test@example.test','role'=>'focal',
   'password'=>'Field-Strong!2026','password_confirmation'=>'Field-Strong!2026',
   'lga_ids'=>[$first->id]])->assertRedirect('/admin/users');
  $focal=User::where('email','field-test@example.test')->firstOrFail();
  $this->assertEquals([$first->id],$focal->assignedLgas->pluck('id')->all());
  $focal->update(['must_change_password'=>false]);
  $this->actingAs($focal)->get('/cases/new')->assertOk()->assertSee('Damaturu')->assertDontSee('Potiskum');
  $data=[
   'client_submission_id'=>(string)Str::uuid(),'type'=>'public_project','title'=>'Synthetic unassigned area test',
   'description'=>'This report should be rejected for wrong assigned local government.',
   'lga_id'=>$other->id,'sector_id'=>Sector::firstOrCreate(['name'=>'Health'])->id,
   'community'=>'QA','observation_date'=>today()->format('Y-m-d'),'progress_status'=>'ongoing'
  ];
  $this->postJson('/cases',$data)->assertUnprocessable()->assertJsonValidationErrors('lga_id');
  $data['lga_id']=$first->id;
  $this->postJson('/cases',$data)->assertCreated();
  $this->assertDatabaseCount('admin_activity_logs',1);
 }
 public function test_meal_can_only_review_unassigned_or_personally_assigned_reports():void {
  $admin=$this->staff('admin');$reviewerA=$this->staff('meal');$reviewerB=$this->staff('meal');$focal=$this->staff('focal');
  $lga=Lga::firstOrCreate(['name'=>'Damaturu'],['code'=>'DAM']);
  $case=$this->case($focal,$lga);
  $this->actingAs($admin)->post('/admin/cases/'.$case->id.'/reviewer',[
   'reviewer_id'=>$reviewerA->id,
   'review_due_at'=>now()->addDays(2)->format('Y-m-d')
  ])->assertRedirect('/cases/'.$case->id);
  $this->assertSame($reviewerA->id,$case->fresh()->reviewer_id);
  $this->assertNotNull($case->fresh()->review_due_at);
  $this->actingAs($reviewerB)->get('/reviews/queue')->assertOk()->assertDontSee($case->reference);
  $this->postJson('/cases/'.$case->id.'/review',['status'=>'under_review'])->assertForbidden();
  $this->actingAs($reviewerA)->get('/reviews/queue')->assertOk()->assertSee($case->reference);
  $this->postJson('/cases/'.$case->id.'/review',['status'=>'under_review'])->assertOk();
  $this->actingAs($admin)->get('/admin/activity')->assertOk()->assertSee('review assignment');
  $this->assertDatabaseHas('admin_activity_logs',['action'=>'review.assignment','actor_id'=>$admin->id]);
 }
 public function test_mfa_policy_requires_an_enrolled_administrator_and_preserves_security_setup_access():void {
  $admin=$this->staff('admin');
  $this->actingAs($admin)->post('/admin/operations/settings',[
   'review_sla_days'=>5,'require_mfa_admin'=>1,'require_mfa_meal'=>1
  ])->assertSessionHasErrors('require_mfa_admin');
  $this->assertFalse(OrganizationSetting::requiresMfa('admin'));
  $admin->forceFill(['two_factor_confirmed_at'=>now()])->save();
  $this->post('/admin/operations/settings',[
   'review_sla_days'=>5,'require_mfa_admin'=>1,'require_mfa_meal'=>1
  ])->assertRedirect('/admin/operations');
  $this->assertSame(5,OrganizationSetting::reviewDays());
  $this->assertTrue(OrganizationSetting::requiresMfa('admin'));
  $meal=$this->staff('meal');
  $this->actingAs($meal)->get('/dashboard')->assertRedirect('/account/security');
  $this->get('/account/security')->assertOk();
  $this->actingAs($admin)->get('/dashboard')->assertOk();
 }
}
