<?php
namespace Tests\Feature;

use App\Models\Community;
use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\ReportCategory;
use App\Models\Sector;
use App\Models\User;
use App\Models\WorkflowStatusLabel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReferenceDataManagementTest extends TestCase
{
 use RefreshDatabase;
 private function user(string $role,string $email):User {
  return User::create(['name'=>'Reference User','email'=>$email,'role'=>$role,
   'password'=>'pass','is_active'=>true,'must_change_password'=>false]);
 }
 public function test_admin_can_manage_categories_communities_and_labels():void {
  $admin=$this->user('admin','references-admin@example.test');
  $lga=Lga::create(['name'=>'Damaturu','code'=>'DAM']);
  $this->actingAs($admin)->get(route('admin.reference.index'))->assertOk()
   ->assertSee('Report Categories')->assertSee('Workflow Status Labels');
  $this->post(route('admin.reference.communities.create'),[
   'name'=>'Nayinawa','lga_id'=>$lga->id
  ])->assertRedirect();
  $this->assertDatabaseHas('communities',['name'=>'Nayinawa','lga_id'=>$lga->id,'is_active'=>true]);
  $categoryInput=['name'=>'Primary Schools','type'=>'public_project'];
  $this->post(route('admin.reference.categories.create'),$categoryInput)->assertRedirect();
  $category=ReportCategory::where('name','Primary Schools')->firstOrFail();
  $this->put(route('admin.reference.categories.update',$category),[
   'name'=>'Public Schools','is_active'=>'0'
  ])->assertRedirect();
  $this->assertFalse((bool)$category->fresh()->is_active);
  $status=WorkflowStatusLabel::where('kind','progress')->where('code','ongoing')->firstOrFail();
  $this->put(route('admin.reference.statuses.update',$status),[
   'label'=>'Work In Progress'
  ])->assertRedirect();
  $this->assertSame('Work In Progress',WorkflowStatusLabel::label('progress','ongoing'));
  $this->assertDatabaseHas('admin_activity_logs',['actor_id'=>$admin->id,'action'=>'status_label.updated']);
 }
 public function test_focal_person_cannot_change_reference_data():void {
  $focal=$this->user('focal','focal-references@example.test');
  $this->actingAs($focal)->get(route('admin.reference.index'))->assertForbidden();
  $this->post(route('admin.reference.categories.create'),[
   'name'=>'Unauthorized Category','type'=>'governance_issue'
  ])->assertForbidden();
  $this->assertSame(0,ReportCategory::count());
 }
 public function test_report_category_must_match_type_and_be_active():void {
  $admin=$this->user('admin','categorization@example.test');
  $lga=Lga::create(['name'=>'Damaturu','code'=>'DAM']);
  $sector=Sector::create(['name'=>'Education']);
  $category=ReportCategory::create(['name'=>'School Projects',
   'type'=>'public_project','is_active'=>true]);
  $payload=[
   'client_submission_id'=>(string)Str::uuid(),
   'type'=>'public_project','title'=>'Building classroom blocks',
   'description'=>'Construction of additional classroom blocks at a local school.',
   'lga_id'=>$lga->id,'community'=>'Old Damaturu','sector_id'=>$sector->id,
   'report_category_id'=>$category->id,
   'observation_date'=>today()->toDateString(),'progress_status'=>'ongoing'
  ];
  $this->actingAs($admin)->post(route('cases.store'),$payload)->assertRedirect();
  $case=CivicCase::firstOrFail();
  $this->assertSame($category->id,$case->report_category_id);
  $payload['client_submission_id']=(string)Str::uuid();
  $payload['type']='governance_issue';
  $this->post(route('cases.store'),$payload)->assertSessionHasErrors('report_category_id');
  $category->update(['is_active'=>false]);
  $payload['type']='public_project';
  $this->post(route('cases.store'),$payload)->assertSessionHasErrors('report_category_id');
 }
 public function test_new_report_form_displays_active_community_suggestions():void {
  $admin=$this->user('admin','communities@example.test');
  $lga=Lga::create(['name'=>'Damaturu','code'=>'DAM']);
  Community::create(['name'=>'Pompomari','lga_id'=>$lga->id,'is_active'=>true]);
  $this->actingAs($admin)->get(route('cases.create'))->assertOk()
   ->assertSee('Pompomari')->assertSee('community-suggestions');
 }
}
