<?php
namespace Tests\Feature;
use Tests\TestCase;
class SystemIconTest extends TestCase
{
    public function test_system_icons_are_valid_and_referenced_by_public_and_staff_pages():void
    {
        $this->assertGreaterThan(1000,filesize(public_path('favicon.ico')));
        $this->assertSame(0,strcmp(substr(file_get_contents(public_path('favicon.ico')),0,4),"\x00\x00\x01\x00"));
        foreach (['icons/act-192.png','icons/act-512.png','icons/apple-touch-icon.png'] as $asset) {
            $file=public_path($asset);
            $this->assertFileExists($file);
            $this->assertGreaterThan(1000,filesize($file));
        }
        $manifest=json_decode(file_get_contents(public_path('manifest.webmanifest')),true,512,JSON_THROW_ON_ERROR);
        $this->assertCount(2,$manifest['icons']);
        $this->assertStringContainsString('act-192.png',$manifest['icons'][0]['src']);
        $this->assertStringContainsString('act-512.png',$manifest['icons'][1]['src']);
        $this->get('/')->assertOk()->assertSee('icons/arewa-handdrawn-map.webp')->assertSee('apple-touch-icon');
        $this->get('/login')->assertOk()->assertSee('icons/arewa-handdrawn-map.webp');
    }
}
