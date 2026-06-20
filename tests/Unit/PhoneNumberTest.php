<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_validates_nine_digit_local_part(): void
    {
        $this->assertTrue(PhoneNumber::isValidLocalPart('888123456'));
        $this->assertFalse(PhoneNumber::isValidLocalPart('88812345'));
        $this->assertFalse(PhoneNumber::isValidLocalPart('0888123456'));
    }

    public function test_normalizes_local_part_to_e164(): void
    {
        $this->assertSame('+359888123456', PhoneNumber::fromLocalPart('888123456'));
    }

    public function test_extracts_local_part_from_stored_values(): void
    {
        $this->assertSame('888123456', PhoneNumber::localPart('+359888123456'));
        $this->assertSame('888123456', PhoneNumber::localPart('0888123456'));
        $this->assertSame('888123456', PhoneNumber::localPart('359888123456'));
    }

    public function test_formats_phone_for_display(): void
    {
        $this->assertSame('088 812 3456', PhoneNumber::formatForDisplay('+359888123456'));
    }

    public function test_masks_trailing_digits_for_display(): void
    {
        $this->assertSame('088 812 *****', PhoneNumber::maskForDisplay('+359888123456'));
    }
}