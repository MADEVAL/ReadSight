<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Language;

use GlobusStudio\ReadSight\Language\LanguageCode;
use PHPUnit\Framework\TestCase;

final class LanguageCodeTest extends TestCase
{
    public function test_creates_normalized_lowercase_code(): void
    {
        $code = new LanguageCode('EN-US');
        $this->assertSame('en-us', $code->value);
    }

    public function test_trims_whitespace(): void
    {
        $code = new LanguageCode('  ru  ');
        $this->assertSame('ru', $code->value);
    }

    public function test_equals_matching_code(): void
    {
        $code1 = new LanguageCode('en-us');
        $code2 = new LanguageCode('en-us');
        $this->assertTrue($code1->equals($code2));
    }

    public function test_equals_different_code(): void
    {
        $code1 = new LanguageCode('en-us');
        $code2 = new LanguageCode('en-gb');
        $this->assertFalse($code1->equals($code2));
    }

    public function test_to_string_returns_value(): void
    {
        $code = new LanguageCode('de-1996');
        $this->assertSame('de-1996', $code->toString());
    }

    public function test_uppercase_normalized_to_lowercase(): void
    {
        $code = new LanguageCode('DE-1996');
        $this->assertSame('de-1996', $code->value);
    }
}

