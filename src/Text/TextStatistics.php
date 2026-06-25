<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Text;

final readonly class TextStatistics
{
    public function __construct(
        public int $letterCount,
        public int $wordCount,
        public int $sentenceCount,
        public int $syllableCount,
        public int $polysyllableCount,
        public float $averageSyllablesPerWord,
        public float $averageWordsPerSentence,
        public int $longWordCount,
        /** @var array<int, int> syllable count => number of words */
        public array $syllableHistogram,
    ) {}
}

