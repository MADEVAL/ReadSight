<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Hyphenation;

use GlobusStudio\ReadSight\Hyphenation\Pattern;
use PHPUnit\Framework\TestCase;

final class PatternTest extends TestCase
{
    public function test_creates_pattern(): void
    {
        $pattern = new Pattern(['a', 'c', 'h'], [0, 0, 0, 4]);
        $this->assertSame(['a', 'c', 'h'], $pattern->chars);
        $this->assertSame([0, 0, 0, 4], $pattern->weights);
        $this->assertSame(3, $pattern->length);
    }

}
