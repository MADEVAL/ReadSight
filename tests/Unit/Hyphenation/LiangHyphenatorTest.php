<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Hyphenation;

use GlobusStudio\ReadSight\Hyphenation\HyphenationOverride;
use GlobusStudio\ReadSight\Hyphenation\HyphenationExceptionsCollection;
use GlobusStudio\ReadSight\Hyphenation\LiangHyphenator;
use GlobusStudio\ReadSight\Hyphenation\Pattern;
use GlobusStudio\ReadSight\Hyphenation\PatternsCollection;
use PHPUnit\Framework\TestCase;

final class LiangHyphenatorTest extends TestCase
{
    private PatternsCollection $patterns;
    private HyphenationExceptionsCollection $exceptions;

    protected function setUp(): void
    {
        $this->patterns = new PatternsCollection();
        $this->exceptions = new HyphenationExceptionsCollection();
    }

    private function createHyphenator(): LiangHyphenator
    {
        return new LiangHyphenator($this->patterns, $this->exceptions, 2, 2);
    }

    public function test_empty_word_returns_empty_array(): void
    {
        $hyphenator = $this->createHyphenator();
        $this->assertSame([], $hyphenator->hyphenate(''));
    }

    public function test_short_word_returns_as_is(): void
    {
        $hyphenator = $this->createHyphenator();
        $this->assertSame(['it'], $hyphenator->hyphenate('it'));
    }

    public function test_exception_word_is_respected(): void
    {
        $this->exceptions->add(new HyphenationOverride('associate', 'as-so-ci-ate'));
        $hyphenator = $this->createHyphenator();

        $result = $hyphenator->hyphenate('associate');
        $this->assertSame(['as', 'so', 'ci', 'ate'], $result);
    }

    public function test_exception_case_insensitive(): void
    {
        $this->exceptions->add(new HyphenationOverride('table', 'ta-ble'));
        $hyphenator = $this->createHyphenator();

        $result = $hyphenator->hyphenate('TABLE');
        $this->assertSame(['TA', 'BLE'], $result);
    }

    public function test_user_hyphenation_overrides_patterns(): void
    {
        $hyphenator = $this->createHyphenator();
        $hyphenator->addHyphenations(['custom' => 'cus-tom']);

        $result = $hyphenator->hyphenate('custom');
        $this->assertSame(['cus', 'tom'], $result);
    }

    public function test_count_syllables(): void
    {
        $this->exceptions->add(new HyphenationOverride('associate', 'as-so-ci-ate'));
        $hyphenator = $this->createHyphenator();

        $this->assertSame(4, $hyphenator->countSyllables('associate'));
    }

    public function test_count_syllables_empty_word(): void
    {
        $hyphenator = $this->createHyphenator();
        $this->assertSame(0, $hyphenator->countSyllables(''));
    }

    public function test_count_syllables_short_word(): void
    {
        $hyphenator = $this->createHyphenator();
        $this->assertSame(1, $hyphenator->countSyllables('it'));
    }

    public function test_pattern_based_splitting(): void
    {
        $this->patterns->add(new Pattern(['a', 'n'], [0, 3, 0]));

        $hyphenator = new LiangHyphenator($this->patterns, $this->exceptions, 1, 1);
        $result = $hyphenator->hyphenate('banana');

        $this->assertCount(3, $result);
        $this->assertSame('ba', $result[0]);
        $this->assertSame('na', $result[1]);
        $this->assertSame('na', $result[2]);
    }

    public function test_complex_word_with_real_en_patterns(): void
    {
        $this->patterns->add(new Pattern(['y', 'p', 'h'], [0, 3, 0, 0]));
        $this->patterns->add(new Pattern(['h', 'e', 'n'], [0, 0, 1, 0]));
        $this->patterns->add(new Pattern(['n', 'a', 't', 'i', 'o', 'n'], [0, 0, 4, 0, 0, 0, 0]));

        $hyphenator = new LiangHyphenator($this->patterns, $this->exceptions, 2, 2);
        $result = $hyphenator->hyphenate('hyphenation');

        $this->assertGreaterThan(1, \count($result));
    }
}
