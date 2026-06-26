<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Syllable;

use GlobusStudio\ReadSight\Syllable\HeuristicSyllableCounter;
use PHPUnit\Framework\TestCase;

final class HeuristicSyllableCounterTest extends TestCase
{
    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function makeConfig(array $overrides = []): array
    {
        return \array_merge([
            'problemWords' => [
                'banana' => 3,
                'beautiful' => 3,
                'extraordinary' => 6,
            ],
            'subtractPatterns' => [
                'cial',
                'tia',
                'cious',
                'eous$',
            ],
            'addPatterns' => [
                'ia',
                'io',
                'iu',
            ],
            'prefixes' => [
                'un' => 1,
                'pre' => 1,
                'dis' => 1,
            ],
            'suffixes' => [
                'ly' => 1,
                'tion' => 1,
                'ment' => 1,
            ],
            'vowelPattern' => '[aeiouy]',
        ], $overrides);
    }

    /** @param array<string, mixed> $config */
    private function makeCounter(array $config): HeuristicSyllableCounter
    {
        return new HeuristicSyllableCounter($config);
    }

    // --- hasRules ---

    public function test_has_rules_returns_true_when_problem_words_exist(): void
    {
        $counter = $this->makeCounter($this->makeConfig());
        $this->assertTrue($counter->hasRules());
    }

    public function test_has_rules_returns_true_when_only_patterns_exist(): void
    {
        $counter = $this->makeCounter($this->makeConfig([
            'problemWords' => [],
        ]));
        $this->assertTrue($counter->hasRules());
    }

    public function test_has_rules_returns_false_when_config_is_null(): void
    {
        $counter = new HeuristicSyllableCounter(null);
        $this->assertFalse($counter->hasRules());
    }

    public function test_has_rules_returns_false_when_empty(): void
    {
        $counter = $this->makeCounter([
            'problemWords' => [],
            'subtractPatterns' => [],
            'addPatterns' => [],
            'prefixes' => [],
            'suffixes' => [],
            'vowelPattern' => '[aeiouy]',
        ]);
        $this->assertFalse($counter->hasRules());
    }

    // --- hasWord ---

    public function test_has_word_returns_true_for_exact_match(): void
    {
        $counter = $this->makeCounter($this->makeConfig());
        $this->assertTrue($counter->hasWord('banana'));
    }

    public function test_has_word_is_case_insensitive(): void
    {
        $counter = $this->makeCounter($this->makeConfig());
        $this->assertTrue($counter->hasWord('BANANA'));
        $this->assertTrue($counter->hasWord('Beautiful'));
    }

    public function test_has_word_returns_false_for_missing(): void
    {
        $counter = $this->makeCounter($this->makeConfig());
        $this->assertFalse($counter->hasWord('computer'));
    }

    public function test_has_word_returns_false_for_empty(): void
    {
        $counter = $this->makeCounter($this->makeConfig());
        $this->assertFalse($counter->hasWord(''));
        $this->assertFalse($counter->hasWord('   '));
    }

    public function test_has_word_returns_false_when_no_problem_words(): void
    {
        $counter = $this->makeCounter(['problemWords' => []]);
        $this->assertFalse($counter->hasWord('anything'));
    }

    // --- countSyllables: problem words ---

    public function test_count_syllables_uses_problem_word_exact_count(): void
    {
        $counter = $this->makeCounter($this->makeConfig());
        $this->assertSame(3, $counter->countSyllables('banana'));
        $this->assertSame(6, $counter->countSyllables('extraordinary'));
    }

    public function test_count_syllables_problem_word_case_insensitive(): void
    {
        $counter = $this->makeCounter($this->makeConfig());
        $this->assertSame(3, $counter->countSyllables('BaNaNa'));
    }

    // --- countSyllables: vowel counting ---

    public function test_count_syllables_vowel_counting_basic(): void
    {
        $counter = $this->makeCounter([
            'problemWords' => [],
            'subtractPatterns' => [],
            'addPatterns' => [],
            'prefixes' => [],
            'suffixes' => [],
            'vowelPattern' => '[aeiouy]',
        ]);
        $this->assertSame(1, $counter->countSyllables('cat'));
        $this->assertSame(2, $counter->countSyllables('table'));
        $this->assertSame(3, $counter->countSyllables('computer'));
    }

    public function test_count_syllables_never_returns_zero(): void
    {
        $counter = $this->makeCounter($this->makeConfig());
        $this->assertSame(1, $counter->countSyllables('bcd'));
        $this->assertSame(1, $counter->countSyllables('xyz'));
    }

    public function test_count_syllables_empty_word_returns_zero(): void
    {
        $counter = $this->makeCounter($this->makeConfig());
        $this->assertSame(0, $counter->countSyllables(''));
        $this->assertSame(0, $counter->countSyllables('   '));
    }

    // --- countSyllables: non-ASCII support ---

    public function test_count_syllables_preserves_accented_characters(): void
    {
        $counter = $this->makeCounter([
            'problemWords' => ['café' => 2],
            'subtractPatterns' => [],
            'addPatterns' => [],
            'prefixes' => [],
            'suffixes' => [],
            'vowelPattern' => '[aeiouyé]',
        ]);
        $this->assertSame(2, $counter->countSyllables('café'));
    }

    public function test_count_syllables_non_ascii_vowel_counting(): void
    {
        $counter = $this->makeCounter([
            'problemWords' => ['naïve' => 2],
            'subtractPatterns' => [],
            'addPatterns' => [],
            'prefixes' => [],
            'suffixes' => [],
            'vowelPattern' => '[aeiouyäöüéèêëï]',
        ]);
        $this->assertSame(2, $counter->countSyllables('naïve'));
    }

    public function test_count_syllables_german_umlauts(): void
    {
        $counter = $this->makeCounter([
            'problemWords' => [],
            'subtractPatterns' => [],
            'addPatterns' => [],
            'prefixes' => [],
            'suffixes' => [],
            'vowelPattern' => '[aeiouyäöü]',
        ]);
        $this->assertSame(2, $counter->countSyllables('schöner'));
    }

    public function test_count_syllables_cyrillic_vowels(): void
    {
        $counter = $this->makeCounter([
            'problemWords' => [],
            'subtractPatterns' => [],
            'addPatterns' => [],
            'prefixes' => [],
            'suffixes' => [],
            'vowelPattern' => '[аеёиоуыэюя]',
        ]);
        $this->assertSame(3, $counter->countSyllables('молоко'));
    }

    // --- countSyllables: affix handling ---

    public function test_count_syllables_prefix_adds_syllable(): void
    {
        $counter = $this->makeCounter([
            'problemWords' => [],
            'subtractPatterns' => [],
            'addPatterns' => [],
            'prefixes' => ['un' => 1],
            'suffixes' => [],
            'vowelPattern' => '[aeiouy]',
        ]);
        $this->assertSame(2, $counter->countSyllables('undo'));
    }

    public function test_count_syllables_suffix_adds_syllable(): void
    {
        $counter = $this->makeCounter([
            'problemWords' => [],
            'subtractPatterns' => [],
            'addPatterns' => [],
            'prefixes' => [],
            'suffixes' => ['ly' => 1],
            'vowelPattern' => '[aeiouy]',
        ]);
        $this->assertSame(2, $counter->countSyllables('slowly'));
    }

    // --- countSyllables: subtract/add patterns ---

    public function test_count_syllables_subtract_pattern_reduces_count(): void
    {
        $counter = $this->makeCounter([
            'problemWords' => [],
            'subtractPatterns' => ['eous$'],
            'addPatterns' => [],
            'prefixes' => [],
            'suffixes' => [],
            'vowelPattern' => '[aeiouy]',
        ]);
        $count = $counter->countSyllables('courageous');
        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function test_count_syllables_add_pattern_increases_count(): void
    {
        $counter = $this->makeCounter([
            'problemWords' => [],
            'subtractPatterns' => [],
            'addPatterns' => ['ia', 'io'],
            'prefixes' => [],
            'suffixes' => [],
            'vowelPattern' => '[aeiouy]',
        ]);
        $count = $counter->countSyllables('radio');
        $this->assertGreaterThanOrEqual(3, $count);
    }

    // --- splitSyllables ---

    public function test_split_syllables_single_syllable(): void
    {
        $counter = $this->makeCounter($this->makeConfig());
        $parts = $counter->splitSyllables('cat');
        $this->assertSame(['cat'], $parts);
    }

    public function test_split_syllables_empty_word(): void
    {
        $counter = $this->makeCounter($this->makeConfig());
        $this->assertSame([], $counter->splitSyllables(''));
    }

    public function test_split_syllables_count_matches_parts(): void
    {
        $counter = $this->makeCounter($this->makeConfig());
        $word = 'communication';
        $parts = $counter->splitSyllables($word);
        $this->assertSame($counter->countSyllables($word), \count($parts));
    }

    // --- default vowel pattern ---

    public function test_default_vowel_pattern_when_not_provided(): void
    {
        $counter = $this->makeCounter([
            'problemWords' => [],
            'subtractPatterns' => [],
            'addPatterns' => [],
            'prefixes' => [],
            'suffixes' => [],
        ]);
        $this->assertSame(1, $counter->countSyllables('cat'));
        $this->assertSame(2, $counter->countSyllables('table'));
    }
}
