<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;
class AndroidTwoFactorLoginTest extends TestCase {
 use RefreshDatabase;
 private function field(bool $enabled):User {
  $u=User::create(['name'=>'Test Field Officer','email'=>'focal-2fa@example.test',
   'password'=>'PasswordForTest!2026','role'=>'focal','is_active'=>true,
   'must_change_password'=>false]);
  if($enabled)$u->forceFill(['two_factor_secret'=>(new Google2FA())->generateSecretKey(),
   'two_factor_confirmed_at'=>now()])->save();
  return $u->fresh();
 }
 public function test_only_enabled_accounts_see_a_challenge_after_credentials_are_valid():void{
  $u=$this->field(true);
  $this->postJson('/api/v1/login',['email'=>$u->email,'password'=>'wrong'])->assertUnprocessable();
  $first=$this->postJson('/api/v1/login',
   ['email'=>$u->email,'password'=>'PasswordForTest!2026'])
   ->assertStatus(202)->assertJsonPath('two_factor_required',true);
  $this->assertNull($first->json('token'));
  $this->assertDatabaseCount('personal_access_tokens',0);
  $challenge=$first->json('challenge');
  $this->assertSame(64,strlen($challenge));
  $otp=(new Google2FA())->getCurrentOtp($u->two_factor_secret);
  $wrong=$otp==='000000'?'111111':'000000';
  $this->postJson('/api/v1/login/verify-otp',
   ['challenge'=>$challenge,'otp'=>$wrong])->assertUnprocessable();
  $this->postJson('/api/v1/login/verify-otp',
   ['challenge'=>$challenge,'otp'=>$otp])->assertOk()->assertJsonPath('user.id',$u->id);
  $this->postJson('/api/v1/login/verify-otp',
   ['challenge'=>$challenge,'otp'=>$otp])->assertUnprocessable();
  $this->assertDatabaseCount('personal_access_tokens',1);
 }
 public function test_no_two_factor_goes_direct_to_login():void{
  $u=$this->field(false);
  $this->postJson('/api/v1/login',
   ['email'=>$u->email,'password'=>'PasswordForTest!2026'])->assertOk()->assertJsonStructure(['token']);
 }
 public function test_unknown_challenge_is_rejected():void{
  $this->postJson('/api/v1/login/verify-otp',
   ['challenge'=>str_repeat('a',64),'otp'=>'123456'])->assertUnprocessable();
 }
}
