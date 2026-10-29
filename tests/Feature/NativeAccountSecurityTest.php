<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Illuminate\Routing\Middleware\ThrottleRequests;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class NativeAccountSecurityTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp():void {parent::setUp();$this->withoutMiddleware(ThrottleRequests::class);}

    private function focal():User {
        return User::create([
            'name'=>'Android Tester','email'=>'native-security@example.test',
            'password'=>'Before-change-2026','role'=>'focal','is_active'=>true,
            'must_change_password'=>false
        ]);
    }

    public function test_profile_requires_authentication_and_reports_only_own_account_fields():void {
        $this->getJson('/api/v1/account/profile')->assertUnauthorized();
        $user=$this->focal();
        Sanctum::actingAs($user,['field:report']);
        $this->getJson('/api/v1/account/profile')->assertOk()
            ->assertJsonPath('user.email',$user->email)
            ->assertJsonPath('user.role','focal')
            ->assertJsonMissingPath('user.password');
    }

    public function test_password_change_requires_current_password_and_confirmation():void {
        $user=$this->focal();
        Sanctum::actingAs($user,['field:report']);
        $this->postJson('/api/v1/account/password',[
            'current_password'=>'wrong',
            'password'=>'After-change-2026','password_confirmation'=>'After-change-2026',
        ])->assertUnprocessable();
        $this->postJson('/api/v1/account/password',[
            'current_password'=>'Before-change-2026',
            'password'=>'After-change-2026','password_confirmation'=>'After-change-2026',
        ])->assertOk();
        $this->assertTrue(Hash::check('After-change-2026',$user->fresh()->password));
    }

    public function test_two_factor_setup_confirm_and_protected_disable():void {
        $user=$this->focal();
        Sanctum::actingAs($user,['field:report']);
        $start=$this->postJson('/api/v1/account/2fa/setup',[
            'current_password'=>'Before-change-2026'
        ])->assertOk()->assertJsonStructure(['secret','uri']);
        $otp=(new Google2FA())->getCurrentOtp($start->json('secret'));
        $result=$this->postJson('/api/v1/account/2fa/confirm',['code'=>$otp])
            ->assertOk()->assertJsonCount(8,'recovery_codes');
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->postJson('/api/v1/account/2fa/disable',[
            'current_password'=>'Before-change-2026','code'=>'000000',
        ])->assertUnprocessable();
        $this->postJson('/api/v1/account/2fa/disable',[
            'current_password'=>'Before-change-2026',
            'code'=>(new Google2FA())->getCurrentOtp($user->fresh()->two_factor_secret)
        ])->assertOk();
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }
}
