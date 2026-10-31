<?php
namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class PublicAndroidDownloadTest extends TestCase
{
    private function withTestApk(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'act-apk-');
        file_put_contents($path, 'test Android artifact');
        config(['native.android_apk_path' => $path]);
        return $path;
    }

    public function test_visitors_can_download_android_without_logging_in(): void
    {
        $path = $this->withTestApk();
        try {
            $this->get(route('downloads.android'))->assertOk()
                ->assertHeader('Content-Type', 'application/vnd.android.package-archive')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Cache-Control', 'private, no-store, no-cache, must-revalidate')
                ->assertHeader('Pragma', 'no-cache')
                ->assertHeader('Expires', '0')
                ->assertDownload('arewa-civic-tracker-v0.4.0-debug.apk');
        } finally {
            unlink($path);
        }
    }

    public function test_a_public_landing_page_links_directly_to_app_download(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Download app')
            ->assertSee(route('downloads.android', ['v' => config('native.android_apk_name')]));
    }

    public function test_versioned_link_returns_current_artifact_without_cache(): void
    {
        $path=$this->withTestApk();
        try {
            $this->get(route('downloads.android', ['v' => config('native.android_apk_name')]))
                ->assertOk()
                ->assertHeader('Cache-Control', 'private, no-store, no-cache, must-revalidate')
                ->assertDownload('arewa-civic-tracker-v0.4.0-debug.apk');
        } finally {
            unlink($path);
        }
    }

    public function test_former_admin_download_address_redirects_to_public_download(): void
    {
        $this->get('/admin/native/android-debug')->assertRedirect('/download/android');
    }

    public function test_missing_apk_does_not_expose_server_file_paths(): void
    {
        config(['native.android_apk_path' => sys_get_temp_dir().'/missing-arewa-civic-test.apk']);
        $this->get(route('downloads.android'))->assertNotFound();
    }

    public function test_private_dashboard_still_requires_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/admin/users')->assertRedirect(route('login'));
    }
}
