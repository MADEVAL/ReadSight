<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Formula;

use GlobusStudio\ReadSight\Formula\TextStatisticsHelper;
use GlobusStudio\ReadSight\Text\TextStatistics;
use PHPUnit\Framework\TestCase;

final class TextStatisticsHelperTest extends TestCase
{
    public function test_empty_text_returns_zero(): void
    {
        $stats = new TextStatistics(
            letterCount: 0,
            wordCount: 0,
            sentenceCount: 0,
            syllableCount: 0,
            polysyllableCount: 0,
            averageSyllablesPerWord: 0.0,
            averageWordsPerSentence: 0.0,
            longWordCount: 0,
            syllableHistogram: [],
        );

        $this->assertSame(0.0, TextStatisticsHelper::estimateDifficultPercentage($stats));
    }

    public function test_all_one_syllable_words_returns_zero(): void
    {
        $stats = new TextStatistics(
            letterCount: 10,
            wordCount: 3,
            sentenceCount: 1,
            syllableCount: 3,
            polysyllableCount: 0,
            averageSyllablesPerWord: 1.0,
            averageWordsPerSentence: 3.0,
            longWordCount: 0,
            syllableHistogram: [1 => 3],
        );

        $this->assertSame(0.0, TextStatisticsHelper::estimateDifficultPercentage($stats));
    }

    public function test_all_multi_syllable_words_returns_100(): void
    {
        $stats = new TextStatistics(
            letterCount: 30,
            wordCount: 4,
            sentenceCount: 1,
            syllableCount: 12,
            polysyllableCount: 2,
            averageSyllablesPerWord: 3.0,
            averageWordsPerSentence: 4.0,
            longWordCount: 0,
            syllableHistogram: [2 => 2, 3 => 2],
        );

        $this->assertSame(100.0, TextStatisticsHelper::estimateDifficultPercentage($stats));
    }

    public function test_mixed_words_calculates_correct_percentage(): void
    {
        $stats = new TextStatistics(
            letterCount: 25,
            wordCount: 5,
            sentenceCount: 1,
            syllableCount: 10,
            polysyllableCount: 1,
            averageSyllablesPerWord: 2.0,
            averageWordsPerSentence: 5.0,
            longWordCount: 0,
            syllableHistogram: [1 => 2, 2 => 2, 3 => 1],
        );

        $this->assertSame(60.0, TextStatisticsHelper::estimateDifficultPercentage($stats));
    }

    public function test_histogram_clamps_negative_difficult_count_to_zero(): void
    {
        $stats = new TextStatistics(
            letterCount: 10,
            wordCount: 3,
            sentenceCount: 1,
            syllableCount: 3,
            polysyllableCount: 0,
            averageSyllablesPerWord: 1.0,
            averageWordsPerSentence: 3.0,
            longWordCount: 0,
            syllableHistogram: [1 => 5],
        );

        $this->assertSame(0.0, TextStatisticsHelper::estimateDifficultPercentage($stats));
    }
}
