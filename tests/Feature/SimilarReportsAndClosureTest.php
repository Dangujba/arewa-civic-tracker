<?php
namespace Tests\Feature;
use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
class SimilarReportsAndClosureTest extends TestCase {
 use RefreshDatabase;
 private function user(string $role,string $email):User{
  return User::create(['name'=>'Test '.$role,'email'=>$email,'password'=>'test-password','role'=>$role,'is_active'=>true,'must_change_password'=>false]);
 }
 private function report(User $reporter,array $changes=[]):CivicCase {
  $lga=Lga::firstOrCreate(['code'=>'DAM'],['name'=>'Damaturu']);
  $sector=Sector::firstOrCreate(['name'=>'Education']);
  if($reporter->role==='focal')$reporter->assignedLgas()->syncWithoutDetaching([$lga->id]);
  return CivicCase::create(array_merge([
   'uuid'=>(string)Str::uuid(),'reference'=>'ACT-YOB-'.strtoupper(Str::random(10)),
   'reporter_id'=>$reporter->id,'client_submission_id'=>(string)Str::uuid(),
   'payload_hash'=>str_repeat('a',64),'type'=>'public_project',
   'title'=>'Damaged classrooms at central primary school',
   'description'=>'Several classrooms require repairs after roof damage.',
   'lga_id'=>$lga->id,'community'=>'Central Damaturu','sector_id'=>$sector->id,
   'observation_date'=>now()->subDay()->toDateString(),
   'progress_status'=>'ongoing','verification_status'=>'submitted'
  ],$changes));
 }
 public function test_similar_warning_masks_other_reporter_and_allows_reviewing_own_case():void {
  $owner=$this->user('focal','owner@example.test');$case=$this->report($owner);
  $other=$this->user('focal','submitter@example.test');
  $other->assignedLgas()->syncWithoutDetaching([$case->lga_id]);
  $input=['title'=>$case->title,'lga_id'=>$case->lga_id,'community'=>$case->community,'sector_id'=>$case->sector_id,'type'=>'public_project','observation_date'=>now()->toDateString()];
  $this->actingAs($other)->postJson(route('cases.similar'),$input)->assertOk()
   ->assertJsonCount(1,'matches')
   ->assertJsonPath('matches.0.title','Related report in this LGA')
   ->assertJsonPath('matches.0.reference',null);
  $this->actingAs($owner)->postJson(route('cases.similar'),$input)->assertOk()
   ->assertJsonPath('matches.0.reference',$case->reference);
 }
 public function test_unassigned_focal_cannot_query_another_lga():void {
  $owner=$this->user('focal','owner@example.test');$case=$this->report($owner);
  $other=$this->user('focal','other@example.test');
  $this->actingAs($other)->postJson(route('cases.similar'),['title'=>$case->title,'lga_id'=>$case->lga_id])->assertForbidden();
 }
 public function test_unrelated_titles_not_flagged():void {
  $admin=$this->user('admin','admin@example.test');$case=$this->report($admin);
  $this->actingAs($admin)->postJson(route('cases.similar'),[
   'title'=>'Water pipeline replacement work','lga_id'=>$case->lga_id,'community'=>$case->community,'sector_id'=>$case->sector_id
  ])->assertOk()->assertJsonCount(0,'matches');
 }
 public function test_admin_can_close_and_reopen_approved_completed_case():void {
  $admin=$this->user('admin','admin@example.test');$case=$this->report($admin,['verification_status'=>'approved','progress_status'=>'completed']);
  $this->actingAs($admin)->post(route('cases.closure',$case),[
   'action'=>'close','reason'=>'Site evidence and approval verified as complete'
  ])->assertRedirect(route('cases.show',$case));
  $this->assertSame('closed',$case->fresh()->closure_status);
  $this->assertDatabaseHas('case_events',['case_id'=>$case->id,'event_type'=>'case_closed']);
  $this->assertDatabaseHas('admin_activity_logs',['action'=>'case.closed','subject_id'=>$case->id]);
  $this->get(route('cases.show',$case))->assertOk()->assertSee('Case Closure');
  $this->post(route('cases.closure',$case),[
   'action'=>'reopen','reason'=>'Additional follow-up evidence requires review'
  ])->assertRedirect(route('cases.show',$case));
  $this->assertSame('open',$case->fresh()->closure_status);
  $this->assertDatabaseHas('case_events',['case_id'=>$case->id,'event_type'=>'case_reopened']);
 }
 public function test_unapproved_or_unresolved_case_cannot_close():void {
  $admin=$this->user('admin','admin@example.test');$case=$this->report($admin);
  $this->actingAs($admin)->post(route('cases.closure',$case),['action'=>'close','reason'=>'Premature closure request'])
   ->assertSessionHasErrors('action');
  $case->update(['verification_status'=>'approved']);
  $this->post(route('cases.closure',$case),['action'=>'close','reason'=>'Unresolved monitoring issue'])
   ->assertSessionHasErrors('action');
 }
 public function test_focal_person_cannot_close_a_case():void {
  $user=$this->user('focal','focal@example.test');$case=$this->report($user,['verification_status'=>'approved','progress_status'=>'resolved']);
  $this->actingAs($user)->post(route('cases.closure',$case),['action'=>'close','reason'=>'Cannot close without admin'])->assertForbidden();
 }
 public function test_closed_case_blocks_edits_followup_progress():void {
  $admin=$this->user('admin','admin@example.test');$case=$this->report($admin,['verification_status'=>'approved','progress_status'=>'resolved']);
  $this->actingAs($admin)->post(route('cases.closure',$case),['action'=>'close','reason'=>'Approved case ready for closure'])->assertRedirect();
  $this->post(route('cases.followups',$case),['note'=>'Unauthorized change on closed case'])->assertForbidden();
  $this->post(route('cases.progress',$case),['progress_status'=>'ongoing','note'=>'Modify a closed case'])->assertForbidden();
  $this->get(route('cases.edit',$case))->assertForbidden();
 }
}
