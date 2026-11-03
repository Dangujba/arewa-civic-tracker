<?php
namespace Tests\Feature;

use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\CaseActionNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CaseDocumentsAndEmailTest extends TestCase
{
 use RefreshDatabase;

 private function fixture(): array {
  $this->seed(DatabaseSeeder::class);
  $user=User::create([
   'name'=>'Report UAT administrator','email'=>'uat-reports@example.test',
   'password'=>'SecureTestPassword2026!','role'=>'admin','is_active'=>true,
   'must_change_password'=>false
  ]);
  $case=CivicCase::create([
   'uuid'=>(string)Str::uuid(),
   'reference'=>'ACT-YOB-PDF-QA',
   'reporter_id'=>$user->id,
   'client_submission_id'=>(string)Str::uuid(),
   'payload_hash'=>Str::random(64),
   'type'=>'public_project',
   'title'=>'Community infrastructure monitoring',
   'description'=>'<p><strong>Road inspection</strong> needs action.</p>',
   'lga_id'=>Lga::firstOrFail()->id,
   'ward'=>'QA Ward','community'=>'QA Community',
   'sector_id'=>Sector::firstOrFail()->id,
   'observation_date'=>'2026-11-01',
   'observation_time'=>'14:15',
   'progress_status'=>'ongoing','verification_status'=>'submitted',
  ]);
  return [$user,$case];
 }

 public function test_case_pdf_and_print_are_private_and_properly_rendered(): void {
  [$u,$c]=$this->fixture();
  $this->get(route('cases.pdf',$c))->assertRedirect(route('login'));
  $this->get(route('cases.print',$c))->assertRedirect(route('login'));
  $this->actingAs($u)->get(route('cases.print',$c))->assertOk()
   ->assertSee('MONITORING REPORT')
   ->assertSee('02:15 PM')
   ->assertSee('Export PDF');
  $response=$this->actingAs($u)->get(route('cases.pdf',$c))->assertOk()
   ->assertHeader('Content-Type','application/pdf');
  $this->assertStringStartsWith('%PDF-', $response->baseResponse->getContent());
 }

 public function test_case_notifications_are_persisted_for_retries_and_send_with_zoho_api(): void {
  [$u,$c]=$this->fixture();
  $u->notify(new CaseActionNotification($c->id,$c->reference,
   'Review required','Review the report from your authorized workspace.'));
  $this->assertDatabaseCount('notifications',1);
  $this->assertDatabaseCount('case_email_outbox',1);
  $this->assertDatabaseHas('case_email_outbox',['recipient_id'=>$u->id,'status'=>'pending']);

  config([
   'arewa_notifications.key'=>'qa-test-key',
   'arewa_notifications.from'=>'verified@example.test',
   'arewa_notifications.endpoint'=>'https://cpaas.zoho.eu/v1.1/email',
  ]);
  Http::fake(['cpaas.zoho.eu/*'=>Http::response(['request_id'=>'qa-accepted'],200)]);
  Artisan::call('act:send-notification-emails');
  Http::assertSentCount(1);
  $this->assertDatabaseHas('case_email_outbox',['recipient_id'=>$u->id,'status'=>'sent']);
 }

 public function test_missing_mail_credentials_keeps_database_notice_and_does_not_send(): void {
  [$u,$c]=$this->fixture();
  $u->notify(new CaseActionNotification($c->id,$c->reference,
   'New assignment','You have a report to review.'));
  config(['arewa_notifications.key'=>null]);
  Http::fake();
  Artisan::call('act:send-notification-emails');
  Http::assertNothingSent();
  $this->assertDatabaseHas('case_email_outbox',['recipient_id'=>$u->id,'status'=>'pending']);
 }
}
