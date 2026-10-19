<?php

namespace Tests\Unit;

use App\Support\RichText;
use Tests\TestCase;

class RichTextTest extends TestCase
{
    public function test_keeps_formatting_and_safe_links(): void
    {
        $html = RichText::sanitize('<p>Hello <strong class="x">car</strong> <a href="https://example.com/car" onclick="alert(1)">site</a></p>');

        $this->assertNotNull($html);
        $this->assertStringContainsString('<strong>car</strong>', $html);
        $this->assertStringContainsString('href="https://example.com/car"', $html);
        $this->assertStringContainsString('rel="nofollow noopener noreferrer"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('class=', $html);
    }

    public function test_unwraps_unsafe_links_and_drops_scripts(): void
    {
        $html = RichText::sanitize('<p><a href="javascript:alert(1)">x</a></p><script>alert(1)</script><div>Second</div>');

        $this->assertNotNull($html);
        $this->assertStringContainsString('<p>x</p>', $html);
        $this->assertStringContainsString('<p>Second</p>', $html);
        $this->assertStringNotContainsString('javascript', $html);
        $this->assertStringNotContainsString('alert', $html);
        $this->assertStringNotContainsString('<a', $html);
    }

    public function test_empty_editor_html_is_null(): void
    {
        $this->assertNull(RichText::sanitize('<p><br></p>'));
        $this->assertNull(RichText::sanitize('<p></p>'));
        $this->assertNull(RichText::sanitize('   '));
    }
}
