<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountSecurityLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_card_renders_inside_security_page_and_main_layout(): void
    {
        $user = User::create([
            'name' => 'Layout Verification User',
            'email' => Str::random(8).'@example.test',
            'password' => 'Layout-Test-Password!2026',
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get('/account/security')->assertOk();
        $html = $response->getContent();
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $this->assertTrue($document->loadHTML($html));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//main//div[contains(concat(" ", normalize-space(@class), " "), " account-page ")]//section[contains(concat(" ", normalize-space(@class), " "), " two-factor-card ")]')->length);
        $this->assertSame(0, $xpath->query('/html/body/section[contains(@class, "two-factor-card")]')->length);
        $this->assertSame(1, $xpath->query('//section[contains(@class, "two-factor-card")]')->length);
        $this->assertSame(1, $xpath->query('//form[@action="'.route('two-factor.setup').'"]')->length);
        $this->assertLessThan(strpos($html,'Two-factor authentication (2FA)'), strpos($html,'Account security'));
    }
}
