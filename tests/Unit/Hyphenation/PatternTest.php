<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Hyphenation;

use GlobusStudio\ReadSight\Hyphenation\Pattern;
use GlobusStudio\ReadSight\Hyphenation\PatternsCollection;
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

final class PatternsCollectionTest extends TestCase
{
    public function test_add_pattern(): void
    {
        $collection = new PatternsCollection();
        $collection->add(new Pattern(['a', 'c', 'h'], [0, 0, 0, 4]));
        $this->assertSame(1, $collection->count());
    }

    public function test_max_length_updates(): void
    {
        $collection = new PatternsCollection();
        $collection->add(new Pattern(['a', 'b'], [0, 5, 0]));
        $this->assertSame(2, $collection->maxLength());
        $collection->add(new Pattern(['a', 'b', 'c', 'd'], [0, 0, 0, 0, 9]));
        $this->assertSame(4, $collection->maxLength());
    }

    public function test_get_weights_returns_correct(): void
    {
        $collection = new PatternsCollection();
        $collection->add(new Pattern(['a', 'c', 'h'], [0, 0, 0, 4]));
        $this->assertSame('0004', $collection->getWeights('ach'));
    }

    public function test_get_weights_returns_null_for_missing(): void
    {
        $collection = new PatternsCollection();
        $this->assertNull($collection->getWeights('xyz'));
    }

    public function test_is_empty(): void
    {
        $collection = new PatternsCollection();
        $this->assertTrue($collection->isEmpty());
        $collection->add(new Pattern(['a'], [0, 5]));
        $this->assertFalse($collection->isEmpty());
    }

    public function test_all_returns_patterns_map(): void
    {
        $collection = new PatternsCollection();
        $collection->add(new Pattern(['a', 'b'], [0, 5, 0]));
        $collection->add(new Pattern(['c', 'd'], [0, 3, 0]));
        $all = $collection->all();
        $this->assertArrayHasKey('ab', $all);
        $this->assertArrayHasKey('cd', $all);
        $this->assertSame('050', $all['ab']);
        $this->assertSame('030', $all['cd']);
    }

    public function test_count_returns_zero_initially(): void
    {
        $collection = new PatternsCollection();
        $this->assertSame(0, $collection->count());
    }
}
