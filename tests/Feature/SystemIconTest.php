<?php
namespace Tests\Feature;

use Tests\TestCase;

class SystemIconTest extends TestCase
{
    public function test_official_uploaded_brand_icons_exist_and_are_referenced(): void
    {
        $icon=public_path('favicon.ico');
        $this->assertFileExists($icon);
        $this->assertGreaterThan(1000,filesize($icon));
        $this->assertSame("\x00\x00\x01\x00",substr(file_get_contents($icon),0,4));
        foreach ([
            'icons/arewa-approved-logo.webp',
            'icons/arewa-approved-wordmark.webp',
            'icons/arewa-approved-mark.webp',
            'icons/arewa-approved-logo.png',
            'icons/arewa-approved-mark-32.png',
            'icons/arewa-approved-mark-192.png',
            'icons/arewa-approved-mark-512.png',
            'icons/apple-touch-icon.png',
        ] as $asset) {
            $file=public_path($asset);
            $this->assertFileExists($file);
            $this->assertGreaterThan(1000,filesize($file));
        }
        $manifest=json_decode(file_get_contents(public_path('manifest.webmanifest')),true,512,JSON_THROW_ON_ERROR);
        $this->assertCount(2,$manifest['icons']);
        $this->assertStringContainsString('arewa-approved-mark-192.png',$manifest['icons'][0]['src']);
        $this->assertStringContainsString('arewa-approved-mark-512.png',$manifest['icons'][1]['src']);
        $this->get('/')->assertOk()->assertSee('icons/arewa-approved-wordmark.webp')->assertSee('apple-touch-icon');
        $this->get('/login')->assertOk()->assertSee('icons/arewa-approved-wordmark.webp');
    }
}
