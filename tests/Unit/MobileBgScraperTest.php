<?php

namespace Tests\Unit;

use App\Services\MobileBg\MobileBgScraper;
use Tests\TestCase;

class MobileBgScraperTest extends TestCase
{
    public function test_parse_description_extracts_text_block_without_section_heading(): void
    {
        $html = <<<'HTML'
<html><body>
<div class="moreInfo">
  <span class="blockTitle"><h2>Допълнителна информация</h2></span><br>
  <div class="text">
    KGM Rexton <br>Промоция до края на месеца. &#9679; 4x4
  </div>
</div>
</body></html>
HTML;

        $scraper = new MobileBgScraper(app(\App\Services\MobileBg\MobileBgClient::class));
        $method = new \ReflectionMethod(MobileBgScraper::class, 'parseDescription');
        $method->setAccessible(true);

        $description = $method->invoke($scraper, $html);

        $this->assertNotNull($description);
        $this->assertStringStartsWith('KGM Rexton', $description);
        $this->assertStringNotContainsString('Допълнителна информация', $description);
        $this->assertStringNotContainsString('<', $description);
        $this->assertStringContainsString('● 4x4', $description);
    }
}