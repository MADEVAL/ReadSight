<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Hyphenation;

use GlobusStudio\ReadSight\Hyphenation\HyphenationException;
use GlobusStudio\ReadSight\Hyphenation\HyphenationExceptionsCollection;
use PHPUnit\Framework\TestCase;

final class HyphenationExceptionsCollectionTest extends TestCase
{
    public function test_add_and_get(): void
    {
        $collection = new HyphenationExceptionsCollection();
        $collection->add(new HyphenationException('associate', 'as-so-ci-ate'));
        $this->assertTrue($collection->has('associate'));
        $this->assertSame('as-so-ci-ate', $collection->get('associate'));
    }

    public function test_has_returns_false_for_missing(): void
    {
        $collection = new HyphenationExceptionsCollection();
        $this->assertFalse($collection->has('nonexistent'));
    }

    public function test_get_returns_null_for_missing(): void
    {
        $collection = new HyphenationExceptionsCollection();
        $this->assertNull($collection->get('nonexistent'));
    }

    public function test_is_empty(): void
    {
        $collection = new HyphenationExceptionsCollection();
        $this->assertTrue($collection->isEmpty());
        $collection->add(new HyphenationException('table', 'ta-ble'));
        $this->assertFalse($collection->isEmpty());
    }

    public function test_count(): void
    {
        $collection = new HyphenationExceptionsCollection();
        $this->assertSame(0, $collection->count());
        $collection->add(new HyphenationException('table', 'ta-ble'));
        $this->assertSame(1, $collection->count());
    }

    public function test_all_returns_map(): void
    {
        $collection = new HyphenationExceptionsCollection();
        $collection->add(new HyphenationException('table', 'ta-ble'));
        $all = $collection->all();
        $this->assertArrayHasKey('table', $all);
        $this->assertSame('ta-ble', $all['table']);
    }
}
