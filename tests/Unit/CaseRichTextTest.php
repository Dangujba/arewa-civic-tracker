<?php
namespace Tests\Unit;

use App\Support\CaseRichText;
use Tests\TestCase;

class CaseRichTextTest extends TestCase
{
    public function test_quill_formatting_is_preserved_without_scripts(): void
    {
        $html = '<h2>Public project</h2><p><strong>Important works</strong> and <em>observations</em>.</p><script>alert(1)</script><p onclick="alert(2)">Details</p>';
        $clean = CaseRichText::clean($html);
        $this->assertStringContainsString('<h2>Public project</h2>', $clean);
        $this->assertStringContainsString('<strong>Important works</strong>', $clean);
        $this->assertStringContainsString('<em>observations</em>', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
    }

    public function test_unsafe_links_and_embedded_media_are_removed(): void
    {
        $html = '<p>See <a href="javascript:alert(2)">unsafe</a> and <a href="https://rawyod.org">safe</a>.</p><iframe src="https://evil.invalid"></iframe>';
        $clean = CaseRichText::clean($html);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('<iframe', $clean);
        $this->assertStringContainsString('https://rawyod.org', $clean);
    }

    public function test_legacy_android_plain_text_remains_plain_and_escaped(): void
    {
        $raw = 'Road project has 2 < 5 completed sections'."\n".'Site officers notified.';
        $this->assertSame($raw, CaseRichText::clean($raw));
        $this->assertStringContainsString('&lt;', CaseRichText::render($raw));
        $this->assertStringContainsString('<br', CaseRichText::render($raw));
    }

    public function test_render_does_not_trust_persisted_formatted_html(): void
    {
        $rendered = CaseRichText::render('<p>Safe report</p><img src=x onerror=alert(1)><svg onload=alert(2)></svg>');
        $this->assertStringContainsString('Safe report', $rendered);
        $this->assertStringNotContainsString('<img', $rendered);
        $this->assertStringNotContainsString('<svg', $rendered);
        $this->assertStringNotContainsString('onerror', $rendered);
    }

    public function test_plain_text_is_available_for_mobile_and_excel(): void
    {
        $this->assertSame('Community road inspection requires attention.',
            CaseRichText::plainText('<p>Community <strong>road</strong> inspection</p><p>requires attention.</p>'));
    }
}
