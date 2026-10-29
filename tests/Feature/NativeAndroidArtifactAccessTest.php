<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NativeAndroidArtifactAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_android_app_can_be_downloaded_by_visitors_and_all_staff_roles(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'act-apk-role-');
        file_put_contents($path, 'test APK artifact');
        config(['native.android_apk_path' => $path]);
        try {
            $url = route('downloads.android');
            $this->get($url)->assertOk();
            foreach (['focal', 'meal', 'admin'] as $role) {
                $user = User::create([
                    'name' => 'APK '.ucfirst($role),
                    'email' => 'apk-'.$role.'@example.test',
                    'password' => 'temporary-test-password',
                    'role' => $role,
                    'is_active' => true,
                    'must_change_password' => false,
                ]);
                $this->actingAs($user)->get($url)->assertOk()
                    ->assertDownload('arewa-civic-tracker-v0.3.7-debug.apk');
            }
        } finally {
            unlink($path);
        }
    }
}

