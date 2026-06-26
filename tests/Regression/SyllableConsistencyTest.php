<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Regression;

use GlobusStudio\ReadSight\Engine;
use PHPUnit\Framework\TestCase;

final class SyllableConsistencyTest extends TestCase
{
    private const DATA_DIR = __DIR__ . '/../../data';

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

    // --- Property-based tests (invariant checks across many inputs) ---

    public function test_fuzz_syllable_count_in_bounds(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $words = [
            '', 'a', 'i', 'the', 'cat', 'dog', 'run', 'go', 'be', 'no',
            'table', 'apple', 'house', 'water', 'light', 'world', 'phone',
            'banana', 'computer', 'elephant', 'umbrella', 'beautiful', 'tomorrow',
            'information', 'university', 'extraordinary', 'communicate', 'relationship',
            'revolutionary', 'internationalization', 'uncharacteristically',
            'antidisestablishmentarianism', 'pneumonoultramicroscopicsilicovolcanoconiosis',
        ];

        foreach ($words as $word) {
            $wordLength = \mb_strlen($word);
            $count = $engine->syllableCount($word);

            if ($wordLength === 0) {
                $this->assertSame(0, $count, "Empty word must have 0 syllables");
            } else {
                $this->assertGreaterThanOrEqual(1, $count, "Word '{$word}' has {$count} syllables (expected >=1)");
                $this->assertLessThanOrEqual($wordLength, $count, "Word '{$word}' has {$count} syllables (expected <= {$wordLength})");
            }
        }
    }

    public function test_fuzz_split_word_reconstructs_original(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $words = [
            'a', 'the', 'cat', 'dog', 'table', 'apple', 'house', 'water', 'light',
            'banana', 'computer', 'elephant', 'umbrella', 'beautiful', 'tomorrow',
            'information', 'university', 'extraordinary', 'communicate',
            'revolutionary', 'international', 'circumstance', 'accommodation',
        ];

        foreach ($words as $word) {
            $parts = $engine->splitWord($word);
            $reconstructed = \implode('', $parts);
            $this->assertSame($word, $reconstructed, "Word '{$word}' not reconstructed from parts: " . \implode('-', $parts));
            $this->assertCount($engine->syllableCount($word), $parts, "Word '{$word}' syllable count mismatch");
        }
    }

    public function test_fuzz_consistent_across_repeated_calls(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $words = [
            'the', 'cat', 'table', 'banana', 'computer', 'elephant', 'beautiful',
            'university', 'extraordinary', 'communication',
        ];

        for ($i = 0; $i < 5; $i++) {
            foreach ($words as $word) {
                $parts1 = $engine->splitWord($word);
                $parts2 = $engine->splitWord($word);
                $this->assertSame($parts1, $parts2, "Word '{$word}' split inconsistently on iteration {$i}");
                $this->assertSame($engine->syllableCount($word), \count($parts1), "Word '{$word}' count mismatch on iteration {$i}");
            }
        }
    }

    public function test_fuzz_empty_and_edge_cases(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $this->assertSame(0, $engine->syllableCount(''));
        $this->assertSame([], $engine->splitWord(''));

        $singleChars = ['a', 'b', 'c', 'z', 'A', 'Z'];
        foreach ($singleChars as $char) {
            $this->assertSame(1, $engine->syllableCount($char), "Char '{$char}'");
            $this->assertSame([$char], $engine->splitWord($char), "Char '{$char}' split");
        }
    }

    public function test_fuzz_multilingual_invariants(): void
    {
        $langWords = [
            'en-us' => ['the', 'table', 'banana', 'computer', 'university'],
            'ru' => ['а', 'мы', 'слово', 'молоко', 'красивый'],
            'de-1996' => ['in', 'und', 'Schreiben', 'Verständlichkeit'],
            'fr' => ['le', 'bonjour', 'ordinateur'],
            'es' => ['y', 'hola', 'hermosa', 'computadora'],
            'it' => ['e', 'ciao', 'bellissimo', 'università'],
            'nl' => ['de', 'goed', 'morgen'],
            'pt' => ['de', 'obrigado', 'computador'],
            'tr' => ['ve', 'merhaba', 'güzel'],
            'pl' => ['i', 'dzień', 'piękny'],
        ];

        foreach ($langWords as $lang => $words) {
            $engine = new Engine($lang, self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
            foreach ($words as $word) {
                $parts = $engine->splitWord($word);
                $reconstructed = \implode('', $parts);
                $this->assertSame($word, $reconstructed, "Lang {$lang}: '{$word}' not reconstructed");

                $count = $engine->syllableCount($word);
                $this->assertGreaterThanOrEqual(1, $count, "Lang {$lang}: '{$word}' has 0 syllables");
                $this->assertLessThanOrEqual(\mb_strlen($word), $count, "Lang {$lang}: '{$word}' has too many syllables");
                $this->assertCount($count, $parts, "Lang {$lang}: '{$word}' count != parts");
            }
        }
    }
}
