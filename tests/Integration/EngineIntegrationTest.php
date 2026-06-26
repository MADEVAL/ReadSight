<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Integration;

use GlobusStudio\ReadSight\Engine;
use PHPUnit\Framework\TestCase;

final class EngineIntegrationTest extends TestCase
{
    private const DATA_DIR = __DIR__ . '/../../data';

    public function test_english_full_patterns_load(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $parts = $engine->splitWord('hyphenation');
        $this->assertGreaterThan(1, \count($parts));
    }

    public function test_russian_full_patterns_load(): void
    {
        $engine = new Engine('ru', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $parts = $engine->splitWord('молоко');
        $this->assertGreaterThan(1, \count($parts));
        $this->assertSame(3, $engine->syllableCount('молоко'));
    }

    public function test_german_full_patterns_load(): void
    {
        $engine = new Engine('de-1996', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $parts = $engine->splitWord('Verständlichkeit');
        $this->assertGreaterThan(1, \count($parts));
    }

    public function test_english_word_counts(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $parts = $engine->splitWord('banana');
        $this->assertGreaterThan(1, \count($parts));
    }

    public function test_english_syllable_count_consistent(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $testWords = [
            'the' => 1,
            'cat' => 1,
            'table' => 2,
            'banana' => 3,
        ];

        foreach ($testWords as $word => $expected) {
            $this->assertSame($expected, $engine->syllableCount($word), "Word: {$word}");
        }
    }

    public function test_analyze_text(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $text = 'The quick brown fox jumps over the lazy dog. This is a simple test.';

        $stats = $engine->analyze($text);

        $this->assertSame(14, $stats->wordCount);
        $this->assertSame(2, $stats->sentenceCount);
        $this->assertGreaterThan(0, $stats->letterCount);
        $this->assertGreaterThan(0, $stats->syllableCount);
        $this->assertGreaterThan(0, $stats->averageSyllablesPerWord);
        $this->assertGreaterThan(0, $stats->averageWordsPerSentence);
    }

    public function test_cache_works(): void
    {
        $cacheDir = \sys_get_temp_dir() . '/readsight-integration-cache';
        if (!\is_dir($cacheDir)) {
            \mkdir($cacheDir, 0777, true);
        }

        $engine1 = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages', $cacheDir);
        $count1 = $engine1->syllableCount('banana');

        $engine2 = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages', $cacheDir);
        $count2 = $engine2->syllableCount('banana');

        $this->assertSame($count1, $count2);

        $cacheFile = $cacheDir . '/syllable.en-us.json';
        $this->assertFileExists($cacheFile);

        \unlink($cacheFile);
        if (\is_dir($cacheDir)) {
            \rmdir($cacheDir);
        }
    }

    public function test_multi_language_engines_work_independently(): void
    {
        $en = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $ru = new Engine('ru', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $this->assertGreaterThan(0, $en->syllableCount('banana'));
        $this->assertGreaterThan(0, $ru->syllableCount('молоко'));
    }

    public function test_get_supported_languages(): void
    {
        $languages = Engine::getSupportedLanguages();
        $this->assertContains('en-us', $languages);
        $this->assertContains('ru', $languages);
        $this->assertContains('de-1996', $languages);
    }
}
