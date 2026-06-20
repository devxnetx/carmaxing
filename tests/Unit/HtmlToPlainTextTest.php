<?php

namespace Tests\Unit;

use App\Support\HtmlToPlainText;
use Tests\TestCase;

class HtmlToPlainTextTest extends TestCase
{
    public function test_converts_mobile_bg_description_html_to_clean_plain_text(): void
    {
        $html = <<<'HTML'
<span class="blockTitle"><h2 style="font-size: 19px">Допълнителна информация</h2></span><br>
<div class="text">
  KGM / Ssang Yong Rexton ! <br>Лятна промоция до изчерпване на количеството. <br>ДВИГАТЕЛ: &#9679; 202к.с.
</div>
HTML;

        $text = HtmlToPlainText::convert($html);

        $this->assertNotNull($text);
        $this->assertStringNotContainsString('<', $text);
        $this->assertStringContainsString('KGM / Ssang Yong Rexton !', $text);
        $this->assertStringContainsString('Лятна промоция', $text);
        $this->assertStringContainsString('● 202к.с.', $text);
        $this->assertStringNotContainsString('      ', $text);
    }

    public function test_sanitize_strips_html_from_mixed_content(): void
    {
        $text = HtmlToPlainText::sanitize('<p>Hello <strong>world</strong></p>');

        $this->assertSame('Hello world', $text);
    }

    public function test_sanitize_removes_mobile_bg_section_heading_from_plain_text(): void
    {
        $text = HtmlToPlainText::sanitize("Допълнителна информация\n\n      \n        KGM Rexton");

        $this->assertSame('KGM Rexton', $text);
    }
}