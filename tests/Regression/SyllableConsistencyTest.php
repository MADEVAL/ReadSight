<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Regression;

use GlobusStudio\ReadSight\Engine;
use PHPUnit\Framework\TestCase;

final class SyllableConsistencyTest extends TestCase
{
    private const string DATA_DIR = __DIR__ . '/../../data';

    public function test_syllable_count_never_negative(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $words = ['hello', 'world', 'banana', 'computer', 'beautiful', '', 'x', 'a'];
        foreach ($words as $word) {
            $count = $engine->syllableCount($word);
            $this->assertGreaterThanOrEqual(0, $count);
        }
    }

    public function test_every_word_has_at_least_one_syllable(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $words = ['the', 'a', 'go', 'do', 'cat', 'dog', 'run', 'red', 'big', 'hot'];
        foreach ($words as $word) {
            $count = $engine->syllableCount($word);
            $this->assertSame(1, $count, "Word '{$word}' should have 1 syllable");
        }
    }

    public function test_text_total_syllables_equals_sum_of_word_syllables(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $text = 'The quick brown fox jumps over the lazy dog.';

        $totalFromText = $engine->totalSyllables($text);
        $words = ['The', 'quick', 'brown', 'fox', 'jumps', 'over', 'the', 'lazy', 'dog'];
        $manualSum = 0;
        foreach ($words as $word) {
            $manualSum += $engine->syllableCount($word);
        }

        $this->assertSame($totalFromText, $manualSum);
    }

    public function test_average_syllables_in_range(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $text = 'The quick brown fox jumps over the lazy dog.';
        $avg = $engine->averageSyllablesPerWord($text);

        $this->assertGreaterThanOrEqual(1.0, $avg);
        $this->assertLessThan(3.0, $avg);
    }

    public function test_histogram_sums_to_word_count(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $text = 'The quick brown fox jumps over the lazy dog.';
        $stats = $engine->analyze($text);

        $histogramSum = \array_sum($stats->syllableHistogram);
        $this->assertSame($stats->wordCount, $histogramSum);
    }

    public function test_syllable_count_consistent_across_repeated_calls(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $words = ['banana', 'hyphenation', 'extraordinary', 'university', 'communication'];
        foreach ($words as $word) {
            $first = $engine->syllableCount($word);
            $second = $engine->syllableCount($word);
            $this->assertSame($first, $second, "Word '{$word}' gives different results");
        }
    }

    public function test_russian_syllable_consistency(): void
    {
        $engine = new Engine('ru', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $word = 'молоко';
        $count = $engine->syllableCount($word);
        $this->assertGreaterThan(1, $count);
        $this->assertLessThanOrEqual(4, $count);

        $parts = $engine->splitWord($word);
        $this->assertCount($count, $parts);
    }

    public function test_german_syllable_consistency(): void
    {
        $engine = new Engine('de-1996', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $words = ['Verständlichkeit', 'Schreiben', 'Deutsch'];
        foreach ($words as $word) {
            $count = $engine->syllableCount($word);
            $parts = $engine->splitWord($word);
            $this->assertCount($count, $parts);
            $this->assertSame($word, \implode('', $parts));
        }
    }

    public function test_split_word_reconstructs_original(): void
    {
        $langs = ['en-us', 'ru', 'de-1996', 'fr', 'es', 'it'];
        $testWords = [
            'en-us' => 'banana',
            'ru' => 'молоко',
            'de-1996' => 'Schreiben',
            'fr' => 'bonjour',
            'es' => 'hola',
            'it' => 'ciao',
        ];

        foreach ($langs as $lang) {
            $engine = new Engine($lang, self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
            $word = $testWords[$lang];
            $parts = $engine->splitWord($word);
            $this->assertSame($word, \implode('', $parts), "Lang: {$lang}");
        }
    }

    public function test_analyze_metrics_are_consistent(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $text = 'The quick brown fox. Another sentence here.';
        $stats = $engine->analyze($text);

        $histogramWordCount = \array_sum($stats->syllableHistogram);
        $this->assertSame($stats->wordCount, $histogramWordCount);
        $this->assertLessThanOrEqual($stats->wordCount, $stats->polysyllableCount);
        $this->assertGreaterThan(0, $stats->letterCount);
        $this->assertGreaterThan(0, $stats->sentenceCount);
    }
}
