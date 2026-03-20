<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\WhatsappNormalizer;
use PHPUnit\Framework\TestCase;

class WhatsappNormalizerTest extends TestCase
{
    public function test_strips_formatting_and_adds_brazil_country_code(): void
    {
        $this->assertSame('5598999999999', WhatsappNormalizer::normalize('(98) 99999-9999'));
    }

    public function test_preserves_existing_55_prefix(): void
    {
        $this->assertSame('5598987654321', WhatsappNormalizer::normalize('+55 (98) 98765-4321'));
    }

    public function test_ten_digit_landline_style_gets_55(): void
    {
        $this->assertSame('553199999999', WhatsappNormalizer::normalize('31 9999-9999'));
    }
}
