<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Hyphenation;

use GlobusStudio\ReadSight\Hyphenation\HyphenationOverride;
use PHPUnit\Framework\TestCase;

final class HyphenationOverrideTest extends TestCase
{
    public function test_creates_exception(): void
    {
        $e = new HyphenationOverride('associate', 'as-so-ci-ate');
        $this->assertSame('associate', $e->word);
        $this->assertSame('as-so-ci-ate', $e->hyphenated);
    }
}
