<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Hyphenation;

use GlobusStudio\ReadSight\Hyphenation\Cache\JsonPatternCache;
use GlobusStudio\ReadSight\Hyphenation\HyphenationException;
use GlobusStudio\ReadSight\Hyphenation\HyphenationExceptionsCollection;
use GlobusStudio\ReadSight\Hyphenation\Pattern;
use GlobusStudio\ReadSight\Hyphenation\PatternsCollection;
use PHPUnit\Framework\TestCase;

final class JsonPatternCacheTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = \sys_get_temp_dir() . '/readsight-test-cache';
        if (!\is_dir($this->cacheDir)) {
            \mkdir($this->cacheDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $files = \glob($this->cacheDir . '/*.json') ?: [];
        foreach ($files as $file) {
            \unlink($file);
        }
        if (\is_dir($this->cacheDir)) {
            \rmdir($this->cacheDir);
        }
    }

    public function test_has_returns_false_initially(): void
    {
        $cache = new JsonPatternCache($this->cacheDir);
        $this->assertFalse($cache->has('en-us'));
    }

    public function test_set_and_get_roundtrip(): void
    {
        $patterns = new PatternsCollection();
        $patterns->add(new Pattern(['a', 'c', 'h'], [0, 0, 0, 4]));
        $patterns->add(new Pattern(['a', 'b', 'a', 'n'], [0, 5, 0, 0, 0]));

        $exceptions = new HyphenationExceptionsCollection();
        $exceptions->add(new HyphenationException('table', 'ta-ble'));

        $data = [
            'patterns' => $patterns,
            'exceptions' => $exceptions,
            'maxPatternLength' => 4,
        ];

        $cache = new JsonPatternCache($this->cacheDir);
        $cache->set('en-us', $data);

        $this->assertTrue($cache->has('en-us'));

        $cached = $cache->get('en-us');
        $this->assertNotNull($cached);
        $this->assertSame(4, $cached['maxPatternLength']);
        $this->assertSame(2, $cached['patterns']->count());
        $this->assertSame(1, $cached['exceptions']->count());
    }

    public function test_get_returns_null_for_missing(): void
    {
        $cache = new JsonPatternCache($this->cacheDir);
        $this->assertNull($cache->get('zz'));
    }

    public function test_clear_removes_cache(): void
    {
        $patterns = new PatternsCollection();
        $patterns->add(new Pattern(['a'], [0, 5]));

        $data = [
            'patterns' => $patterns,
            'exceptions' => new HyphenationExceptionsCollection(),
            'maxPatternLength' => 1,
        ];

        $cache = new JsonPatternCache($this->cacheDir);
        $cache->set('en-us', $data);
        $this->assertTrue($cache->has('en-us'));

        $cache->clear('en-us');
        $this->assertFalse($cache->has('en-us'));
    }

    public function test_clear_all_removes_all(): void
    {
        $patterns = new PatternsCollection();
        $patterns->add(new Pattern(['a'], [0, 5]));
        $data = [
            'patterns' => $patterns,
            'exceptions' => new HyphenationExceptionsCollection(),
            'maxPatternLength' => 1,
        ];

        $cache = new JsonPatternCache($this->cacheDir);
        $cache->set('en-us', $data);
        $cache->set('ru', $data);
        $this->assertTrue($cache->has('en-us'));
        $this->assertTrue($cache->has('ru'));

        $cache->clearAll();
        $this->assertFalse($cache->has('en-us'));
        $this->assertFalse($cache->has('ru'));
    }
}

