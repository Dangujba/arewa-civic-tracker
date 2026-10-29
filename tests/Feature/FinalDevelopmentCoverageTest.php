<?php
namespace Tests\Feature;
use App\Models\Lga;
use App\Models\User;
use App\Models\WorkflowStatusLabel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
class FinalDevelopmentCoverageTest extends TestCase {
 use RefreshDatabase;
 private function actor(string $role):User {
  return User::create(['name'=>'Development '.$role,'email'=>Str::random(12).'@example.test',
   'password'=>'Long-strong-password!2026','role'=>$role,'is_active'=>true,'must_change_password'=>false]);
 }
 public function test_only_admin_manages_lgas_with_unique_codes_and_existing_ids_preserved():void {
  $admin=$this->actor('admin');$focal=$this->actor('focal');
  $this->actingAs($focal)->post('/admin/reference-data/lgas',['name'=>'Sample District','code'=>'TST'])->assertForbidden();
  $this->actingAs($admin)->post('/admin/reference-data/lgas',['name'=>'Sample District','code'=>'tst'])->assertRedirect();
  $lga=Lga::where('name','Sample District')->firstOrFail();
  $this->assertSame('TST',$lga->code);
  $this->actingAs($admin)->post('/admin/reference-data/lgas',['name'=>'Other District','code'=>'TST'])->assertSessionHasErrors('code');
  $this->actingAs($admin)->put('/admin/reference-data/lgas/'.$lga->id,['name'=>'Renamed District','code'=>'REN'])->assertRedirect();
  $this->assertSame($lga->id,Lga::where('name','Renamed District')->firstOrFail()->id);
  $this->assertDatabaseHas('admin_activity_logs',['action'=>'lga.updated','subject_id'=>$lga->id]);
 }
 public function test_community_analytics_filters_drilldown_and_status_labels():void {
  $admin=$this->actor('admin');
  $lga=Lga::firstOrCreate(['name'=>'Damaturu'],['code'=>'DAM']);
  $sector=\App\Models\Sector::firstOrCreate(['name'=>'Health']);
  foreach(['Central Ward','Central Ward','North Ward'] as $i=>$community){
   $this->actingAs($admin)->postJson('/cases',[
    'client_submission_id'=>(string)Str::uuid(),'type'=>'public_project',
    'title'=>'Synthetic report '.($i+1),'description'=>'Synthetic observation for development verification',
    'lga_id'=>$lga->id,'community'=>$community,'sector_id'=>$sector->id,
    'observation_date'=>today()->toDateString(),'progress_status'=>'ongoing'
   ])->assertCreated();
  }
  $this->actingAs($admin)->get('/reports/analytics')->assertOk()
   ->assertSee('Reports by community')->assertSee('Central Ward')->assertSee('North Ward');
  $this->actingAs($admin)->get('/reports/analytics?community=Central%20Ward')->assertOk()
   ->assertSee('Matching reports')->assertSee('2</strong>',false)->assertDontSee('Synthetic report 3');
  WorkflowStatusLabel::where('kind','verification')->where('code','submitted')->update(['label'=>'Awaiting review by RAWYOD']);
  $this->actingAs($admin)->get('/reports/analytics')->assertOk()->assertSee('Awaiting review by RAWYOD');
  $this->actingAs($admin)->get('/cases?verification_status=submitted')->assertOk()->assertSee('Awaiting review by RAWYOD');
  $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Awaiting review by RAWYOD');
  $csv=$this->actingAs($admin)->get('/cases/export.csv')->assertOk()->streamedContent();
  $this->assertStringContainsString('Awaiting review by RAWYOD',$csv);
 }
}
