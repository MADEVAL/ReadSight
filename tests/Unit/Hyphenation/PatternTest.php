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

    public function test_to_string_basic(): void
    {
        $pattern = new Pattern(['a', 'b'], [0, 5, 0]);
        $this->assertSame('a5b', $pattern->toString());
    }

    public function test_to_string_with_trailing_weight(): void
    {
        $pattern = new Pattern(['a', 'c', 'h'], [0, 0, 0, 4]);
        $this->assertSame('ach4', $pattern->toString());
    }

    public function test_to_string_with_mixed_weights(): void
    {
        $pattern = new Pattern(['a', 'b', 'a', 'n'], [0, 5, 0, 0, 0]);
        $this->assertSame('a5ban', $pattern->toString());
    }
}
