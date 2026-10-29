<?php
namespace Tests\Feature;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
class AndroidFieldApiTest extends TestCase {
 use RefreshDatabase;
 private function user(string $role):User{
  return User::create(['name'=>'Field API Tester','email'=>Str::random(10).'@example.test',
   'password'=>'Secure-Field-Test!2026','role'=>$role,'is_active'=>true,'must_change_password'=>false]);
 }
 private function login(User $user):string {
  return $this->postJson('/api/v1/login',['email'=>$user->email,'password'=>'Secure-Field-Test!2026',
   'device_name'=>'QA-Android'])->assertOk()->json('token');
 }
 public function test_token_scope_login_and_logout():void{
  $u=$this->user('focal');$token=$this->login($u);
  $this->assertNotEmpty($token);
  $this->getJson('/api/v1/bootstrap')->assertUnauthorized();
  $this->withToken($token)->getJson('/api/v1/bootstrap')->assertOk()->assertJsonPath('user.id',$u->id);
  $this->postJson('/api/v1/logout')->assertOk();
  app('auth')->forgetGuards();
  $this->getJson('/api/v1/bootstrap')->assertUnauthorized();
 }
 public function test_android_token_requires_active_focal_and_first_login_security():void{
  $admin=$this->user('admin');
  $this->postJson('/api/v1/login',['email'=>$admin->email,'password'=>'Secure-Field-Test!2026'])
   ->assertUnprocessable();
  $u=$this->user('focal');$u->update(['must_change_password'=>true]);
  $this->postJson('/api/v1/login',['email'=>$u->email,'password'=>'Secure-Field-Test!2026'])
   ->assertForbidden();
  $u->update(['must_change_password'=>false,'is_active'=>false]);
  $this->postJson('/api/v1/login',['email'=>$u->email,'password'=>'Secure-Field-Test!2026'])
   ->assertUnprocessable();
 }
 public function test_offline_uuid_replay_lga_authorization_and_report_visibility():void{
  $u=$this->user('focal');$other=$this->user('focal');
  $lga=Lga::firstOrCreate(['name'=>'Damaturu'],['code'=>'DAM']);
  $elsewhere=Lga::firstOrCreate(['name'=>'Potiskum'],['code'=>'POT']);
  $sector=Sector::firstOrCreate(['name'=>'Education']);
  $u->assignedLgas()->sync([$lga->id]);
  $token=$this->login($u);
  $payload=['client_submission_id'=>(string)Str::uuid(),'type'=>'public_project',
   'title'=>'Offline Android QA case','description'=>'A simulated Android offline data synchronization report.',
   'lga_id'=>$lga->id,'community'=>'TEST ONLY','sector_id'=>$sector->id,
   'observation_date'=>today()->toDateString(),'progress_status'=>'ongoing'];
  $bad=[...$payload,'lga_id'=>$elsewhere->id];
  $this->withToken($token)->postJson('/api/v1/reports',$bad)->assertUnprocessable()->assertJsonValidationErrors('lga_id');
  $created=$this->postJson('/api/v1/reports',$payload)->assertCreated();
  $id=$created->json('id');
  $this->postJson('/api/v1/reports',$payload)->assertCreated()->assertJsonPath('id',$id);
  $this->getJson('/api/v1/reports')->assertOk()->assertJsonCount(1,'reports');
  app('auth')->forgetGuards();
  $otherToken=$this->login($other);
  app('auth')->forgetGuards();
  $this->withToken($otherToken)->getJson('/api/v1/reports/'.$id)->assertForbidden();
  $this->assertDatabaseCount('civic_cases',1);
 }
}
