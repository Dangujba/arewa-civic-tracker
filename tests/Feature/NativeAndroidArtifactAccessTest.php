<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NativeAndroidArtifactAccessTest extends TestCase
{
    use RefreshDatabase;
    private function account(string $role,string $email):User {
        return User::create([
            'name'=>'APK Test','email'=>$email,'password'=>'pass',
            'role'=>$role,'is_active'=>true,'must_change_password'=>false
        ]);
    }

    public function test_debug_apk_requires_an_authenticated_administrator():void {
        $url=route('admin.native.android-debug');
        $this->get($url)->assertRedirect(route('login'));
        $focal=$this->account('focal','apk-field@example.test');
        $this->actingAs($focal)->get($url)->assertForbidden();
        $admin=$this->account('admin','apk-admin@example.test');
        $path=storage_path('app/private/native-release/arewa-civic-tracker-v0.3.0-debug.apk');
        @mkdir(dirname($path),0700,true);
        file_put_contents($path,'temporary-test-content');
        try {
            $this->actingAs($admin)->get($url)->assertOk()
                ->assertHeader('Content-Type','application/vnd.android.package-archive');
        } finally { unlink($path); }
    }
}
