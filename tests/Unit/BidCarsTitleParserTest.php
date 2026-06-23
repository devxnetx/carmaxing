<?php

namespace Tests\Unit;

use App\Services\BidCars\BidCarsTitleParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BidCarsTitleParserTest extends TestCase
{
    #[Test]
    public function test_it_parses_year_make_model_and_variant(): void
    {
        $parser = new BidCarsTitleParser;

        $parsed = $parser->parse('2014 BMW X3, Xdrive28I');

        $this->assertSame(2014, $parsed['year']);
        $this->assertSame('BMW', $parsed['make']);
        $this->assertSame('X3', $parsed['model']);
        $this->assertSame('Xdrive28I', $parsed['variant']);
    }

    #[Test]
    public function test_it_parses_multi_word_models(): void
    {
        $parser = new BidCarsTitleParser;

        $parsed = $parser->parse('2015 Mercedes-Benz C-Class, 4Matic');

        $this->assertSame(2015, $parsed['year']);
        $this->assertSame('Mercedes-Benz', $parsed['make']);
        $this->assertSame('C-Class', $parsed['model']);
        $this->assertSame('4Matic', $parsed['variant']);
    }
}