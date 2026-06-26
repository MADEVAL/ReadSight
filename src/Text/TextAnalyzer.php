<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Text;

use GlobusStudio\ReadSight\Exception\EmptyTextException;
use GlobusStudio\ReadSight\Hyphenation\Hyphenator;
use GlobusStudio\ReadSight\Hyphenation\LiangHyphenator;
use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Syllable\SyllableCounter;

final readonly class TextAnalyzer
{
    public function __construct(
        private Hyphenator $hyphenator,
        private SyllableCounter $syllableCounter,
        private TextSplitter $textSplitter,
        private Language $language,
    ) {
    }

    /** @return list<string> */
    public function splitWord(string $word): array
    {
        return $this->hyphenator->hyphenate($word);
    }

    /** @return list<string> */
    public function splitSyllables(string $word): array
    {
        return $this->syllableCounter->splitSyllables($word);
    }

    public function syllableCount(string $word): int
    {
        return $this->syllableCounter->countSyllables($word);
    }

    public function wordCount(string $text): int
    {
        return $this->textSplitter->countWords($text);
    }

    public function sentenceCount(string $text): int
    {
        return $this->textSplitter->countSentences($text);
    }

    public function letterCount(string $text): int
    {
        return $this->textSplitter->countLetters($text);
    }

    public function totalSyllables(string $text): int
    {
        $words = $this->textSplitter->splitWords($text);
        $total = 0;

        foreach ($words as $word) {
            $total += $this->syllableCounter->countSyllables($word);
        }

        return $total;
    }

    public function averageSyllablesPerWord(string $text): float
    {
        $words = $this->textSplitter->splitWords($text);
        $wordCount = \count($words);

        if ($wordCount === 0) {
            return 0.0;
        }

        $total = 0;
        foreach ($words as $word) {
            $total += $this->syllableCounter->countSyllables($word);
        }

        return $total / $wordCount;
    }

    public function averageWordsPerSentence(string $text): float
    {
        $wordCount = $this->textSplitter->countWords($text);
        $sentenceCount = $this->textSplitter->countSentences($text);

        if ($sentenceCount === 0) {
            return (float) $wordCount;
        }

        return $wordCount / $sentenceCount;
    }

    public function wordsWithMoreThanNSyllables(string $text, int $n, bool $countProperNouns = true): int
    {
        $words = $this->textSplitter->splitWords($text);
        $count = 0;

        foreach ($words as $word) {
            if ($this->syllableCounter->countSyllables($word) > $n) {
                if ($countProperNouns) {
                    $count++;
                } else {
                    $firstLetter = \mb_substr($word, 0, 1);
                    if ($firstLetter !== \mb_strtoupper($firstLetter)) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    public function polysyllableCount(string $text, bool $countProperNouns = true): int
    {
        return $this->wordsWithMoreThanNSyllables($text, 2, $countProperNouns);
    }

    /** @return array<int, int> */
    public function histogramSyllables(string $text): array
    {
        $words = $this->textSplitter->splitWords($text);
        $histogram = [];

        foreach ($words as $word) {
            $syllables = $this->syllableCounter->countSyllables($word);
            if ($syllables === 0) {
                continue;
            }
            $histogram[$syllables] = ($histogram[$syllables] ?? 0) + 1;
        }

        \ksort($histogram);

        return $histogram;
    }

    public function analyze(string $text): TextStatistics
    {
        $text = \trim($text);

        $words = $this->textSplitter->splitWords($text);
        $wordCount = \count($words);

        if ($wordCount === 0) {
            throw EmptyTextException::create();
        }

        $letterCount = $this->textSplitter->countLetters($text);
        $sentenceCount = $this->textSplitter->countSentences($text);

        $totalSyllables = 0;
        $polysyllableCount = 0;
        $histogram = [];

        foreach ($words as $word) {
            $syllables = $this->syllableCounter->countSyllables($word);
            $totalSyllables += $syllables;

            if ($syllables > 2) {
                $polysyllableCount++;
            }

            if ($syllables > 0) {
                $histogram[$syllables] = ($histogram[$syllables] ?? 0) + 1;
            }
        }

        $sentenceCountForAverage = $sentenceCount === 0 ? 1 : $sentenceCount;

        \ksort($histogram);

        $lixConfig = $this->language->getFormulaConfig('lix');
        $longWordThreshold = 6;
        if (\is_array($lixConfig) && isset($lixConfig['longWordThreshold']) && \is_numeric($lixConfig['longWordThreshold'])) {
            $longWordThreshold = (int) $lixConfig['longWordThreshold'];
        }
        $longWordCount = $this->textSplitter->countLongWords($text, $longWordThreshold);

        return new TextStatistics(
            letterCount: $letterCount,
            wordCount: $wordCount,
            sentenceCount: $sentenceCount,
            syllableCount: $totalSyllables,
            polysyllableCount: $polysyllableCount,
            averageSyllablesPerWord: $totalSyllables / $wordCount,
            averageWordsPerSentence: $wordCount / $sentenceCountForAverage,
            longWordCount: $longWordCount,
            syllableHistogram: $histogram,
        );
    }

    /** @param array<string, string> $hyphenations */
    public function addHyphenations(array $hyphenations): void
    {
        if ($this->hyphenator instanceof LiangHyphenator) {
            $this->hyphenator->addHyphenations($hyphenations);
        }
    }
}
