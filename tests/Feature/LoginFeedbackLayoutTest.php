<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginFeedbackLayoutTest extends TestCase
{
    public function test_login_feedback_is_above_inputs_and_has_no_inline_status(): void
    {
        $response = $this->get('/login')->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('id="login-feedback"', $html);
        $this->assertStringContainsString('arewa-fieldwork-hero.webp', $html);
        $this->assertStringNotContainsString('Evidence. Trust.', $html);
        $this->assertStringContainsString('auth-art auth-art-image', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringNotContainsString('Secure sign in', $html);
        $this->assertStringNotContainsString('data-form-status', $html);
        $this->assertTrue(strpos($html, 'id="login-feedback"') < strpos($html, 'id="email"'));
        $this->assertTrue(strpos($html, 'id="login-feedback"') < strpos($html, 'id="password"'));
        $this->assertStringContainsString('>Sign in <i class="fa-solid fa-arrow-right"', $html);
    }
}
