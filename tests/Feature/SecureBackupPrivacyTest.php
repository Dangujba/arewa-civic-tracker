<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecureBackupPrivacyTest extends TestCase {
 use RefreshDatabase;
 public function test_backup_fails_safely_without_encryption_key():void {
  config()->set('act.backup_key_file','/tmp/arewa-no-such-backup-key');
  $this->artisan('act:secure-backup')->assertExitCode(1);
 }
 public function test_privacy_maintenance_only_prunes_expired_authentication_artifacts():void {
  $user=User::create(['name'=>'Auth Test','email'=>'auth-backup@example.test',
   'password'=>'pass','role'=>'focal','is_active'=>true,'must_change_password'=>false]);
  $expired=$user->createToken('expired', ['field:report'],now()->subDay())->accessToken;
  $active=$user->createToken('active', ['field:report'],now()->addDay())->accessToken;
  foreach(['stale'=>now()->subDays(45)->timestamp,'recent'=>now()->timestamp] as $id=>$time){
   DB::table('sessions')->insert(['id'=>'auth-'.$id,'user_id'=>$user->id,'ip_address'=>'127.0.0.1',
    'user_agent'=>'test','payload'=>'test','last_activity'=>$time]);
  }
  $this->artisan('act:privacy-maintenance')->assertExitCode(0);
  $this->assertDatabaseMissing('personal_access_tokens',['id'=>$expired->id]);
  $this->assertDatabaseHas('personal_access_tokens',['id'=>$active->id]);
  $this->assertDatabaseMissing('sessions',['id'=>'auth-stale']);
  $this->assertDatabaseHas('sessions',['id'=>'auth-recent']);
  $this->assertDatabaseHas('users',['id'=>$user->id]);
 }
}
