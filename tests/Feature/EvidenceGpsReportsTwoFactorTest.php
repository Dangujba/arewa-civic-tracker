<?php
namespace Tests\Feature;

use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class EvidenceGpsReportsTwoFactorTest extends TestCase
{
 use RefreshDatabase;

 private function user(string $role='admin'): User{
  return User::create(['name'=>'QA Authorized '.$role,'email'=>Str::random(10).'@example.test',
   'password'=>'Current-Strong!2026','is_active'=>true,'role'=>$role,'must_change_password'=>false]);
 }
 private function payload():array{
  $lga=Lga::firstOrCreate(['name'=>'Damaturu'],['code'=>'DAM']);
  $sector=Sector::firstOrCreate(['name'=>'Health']);
  return [
   'client_submission_id'=>(string)Str::uuid(), 'type'=>'public_project',
   'title'=>'QA synthetic building inspection','description'=>'This is an isolated QA report with captured evidence.',
   'lga_id'=>$lga->id,'community'=>'QA community','sector_id'=>$sector->id,
   'observation_date'=>today()->toDateString(),'progress_status'=>'ongoing',
   'latitude'=>11.7485,'longitude'=>11.9669,
  ];
 }
 public function test_initial_submission_uploads_multiple_evidence_images_atomically_and_replays_without_duplication():void{
  Storage::fake('local');$focal=$this->user('focal');$data=$this->payload();
  $a=UploadedFile::fake()->image('first.jpg',640,480);
  $b=UploadedFile::fake()->image('second.png',500,500);
  $response=$this->actingAs($focal)->withHeaders(['Accept'=>'application/json'])->post('/cases',[...$data,'evidence'=>[$a,$b]]);
  $response->assertCreated();
  $case=CivicCase::findOrFail($response->json('id'));
  $this->assertSame(2,$case->evidence()->count());
  $this->assertDatabaseHas('civic_cases',['id'=>$case->id,'latitude'=>11.7485,'longitude'=>11.9669]);
  foreach($case->evidence as $e)Storage::disk('local')->assertExists($e->path);
  $again=$this->actingAs($focal)->withHeaders(['Accept'=>'application/json'])->post('/cases',[...$data,'evidence'=>[
    UploadedFile::fake()->image('first.jpg',640,480),
    UploadedFile::fake()->image('second.png',500,500)
  ]])->assertCreated();
  $this->assertSame($case->id,$again->json('id'));
  $this->assertDatabaseCount('civic_cases',1);
  $this->assertSame(3,$case->fresh()->events()->count());
  $this->assertSame(2,$case->fresh()->evidence()->count());
 }
 public function test_report_form_has_initial_evidence_and_editable_auto_gps_interface():void{
  $this->actingAs($this->user('focal'))->get('/cases/new')->assertOk()
   ->assertSee('evidence[]')->assertSee('act-report-location.js')
   ->assertSee('gps-capture')->assertSee('latitude-field')->assertSee('longitude-field')
   ->assertSee('Photographs');
 }
 public function test_admin_reports_page_native_excel_and_pdf_work_but_focal_cannot_export():void{
  $admin=$this->user();$data=$this->payload();
  $created=$this->actingAs($admin)->postJson('/cases',$data)->assertCreated();
  $this->actingAs($admin)->get('/reports/analytics')->assertOk()->assertSee('Reports &amp; Analytics',false)
    ->assertSee('Export Excel')->assertSee('Export PDF')->assertSee($data['title']);
  $xlsx=$this->actingAs($admin)->get('/reports/export.xlsx')->assertOk();
  $this->assertStringContainsString('spreadsheetml',$xlsx->headers->get('Content-Type',''));
  $this->assertStringContainsString('.xlsx',$xlsx->headers->get('Content-Disposition',''));
  $pdf=$this->actingAs($admin)->get('/reports/export.pdf')->assertOk();
  $this->assertStringStartsWith('%PDF-',$pdf->getContent());
  $this->actingAs($admin)->get('/reports/analytics?q=unmatched')->assertOk()->assertSee('No matching reports.');
  $this->actingAs($this->user('focal'))->get('/reports/export.xlsx')->assertForbidden();
  $this->get('/reports/export.pdf')->assertForbidden();
 }
 public function test_totp_setup_challenge_recovery_codes_and_disable_are_enforced():void{
  $user=$this->user();
  $this->actingAs($user)->post('/account/two-factor/setup',['current_password'=>'Current-Strong!2026'])
   ->assertRedirect('/account/security');
  $secret=session('two_factor_setup_secret');
  $this->assertNotEmpty($secret);
  $this->actingAs($user)->get('/account/security')->assertOk()->assertSee('data:image/png;base64',false);
  $otp=(new Google2FA())->getCurrentOtp($secret);
  $confirm=$this->actingAs($user)->post('/account/two-factor/confirm',['code'=>$otp]);
  $confirm->assertRedirect('/account/security');
  $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
  $this->assertSame(8,count($user->fresh()->two_factor_recovery_codes));
  $codes=session('recovery_codes');
  $this->assertCount(8,$codes);
  $this->post('/logout')->assertRedirect('/login');
  $this->post('/login',['email'=>$user->email,'password'=>'Current-Strong!2026'])
   ->assertRedirect('/two-factor/challenge');
  $this->assertGuest();
  $this->get('/dashboard')->assertRedirect('/login');
  $this->post('/two-factor/challenge',['code'=>'000000'])->assertSessionHasErrors('code');
  $this->post('/two-factor/challenge',['code'=>$otp])->assertRedirect('/dashboard');
  $this->assertAuthenticatedAs($user);
  $this->post('/logout')->assertRedirect('/login');
  $this->post('/login',['email'=>$user->email,'password'=>'Current-Strong!2026'])
   ->assertRedirect('/two-factor/challenge');
  $this->post('/two-factor/challenge',['code'=>$codes[0]])->assertRedirect('/dashboard');
  $this->assertCount(7,$user->fresh()->two_factor_recovery_codes);
  $this->post('/account/two-factor/disable',['current_password'=>'Current-Strong!2026','code'=>$otp])->assertRedirect('/login');
  $this->assertNull($user->fresh()->two_factor_confirmed_at);
  $this->post('/login',['email'=>$user->email,'password'=>'Current-Strong!2026'])->assertRedirect('/dashboard');
 }
}
