<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Hyphenation;

final class LiangHyphenator implements Hyphenator
{
    private int $minHyphenLeft;
    private int $minHyphenRight;

    /** @var array<string, string> */
    private array $userHyphenations = [];

    public function __construct(
        private readonly PatternsCollection $patterns,
        private readonly HyphenationExceptionsCollection $exceptions,
        int $minHyphenLeft = 2,
        int $minHyphenRight = 2,
    ) {
        $this->minHyphenLeft = $minHyphenLeft;
        $this->minHyphenRight = $minHyphenRight;
    }

    /** @param array<string, string> $hyphenations Word => hyphenated-form */
    public function addHyphenations(array $hyphenations): void
    {
        foreach ($hyphenations as $word => $hyphenated) {
            $this->userHyphenations[\mb_strtolower($word)] = \mb_strtolower($hyphenated);
        }
    }

    /** @return list<string> */
    public function hyphenate(string $word): array
    {
        $wordLength = \mb_strlen($word);

        if ($wordLength === 0) {
            return [];
        }

        if ($wordLength < $this->minHyphenLeft + $this->minHyphenRight) {
            return [$word];
        }

        $wordLower = \mb_strtolower($word);

        if (isset($this->userHyphenations[$wordLower])) {
            return $this->splitByHyphenation($this->userHyphenations[$wordLower], $word);
        }

        if ($this->exceptions->has($wordLower)) {
            $hyphenated = $this->exceptions->get($wordLower);
            if ($hyphenated !== null) {
                return $this->splitByHyphenation($hyphenated, $word);
            }
        }

        return $this->splitByPatterns($word, $wordLength, $wordLower);
    }

    public function countSyllables(string $word): int
    {
        $parts = $this->hyphenate($word);

        return $parts === [] ? 0 : \count($parts);
    }

    /** @return list<string> */
    private function splitByHyphenation(string $hyphenated, string $originalWord): array
    {
        $parts = [];
        $part = '';
        $j = 0;
        $hyphenatedLength = \mb_strlen($hyphenated);

        for ($i = 0; $i < $hyphenatedLength; $i++) {
            $char = \mb_substr($hyphenated, $i, 1);
            if ($char === '-') {
                $parts[] = $part;
                $part = '';
            } else {
                $part .= \mb_substr($originalWord, $j, 1);
                $j++;
            }
        }

        if ($part !== '') {
            $parts[] = $part;
        }

        return $parts;
    }

    /** @return list<string> */
    private function splitByPatterns(string $word, int $wordLength, string $wordLower): array
    {
        $text = '.' . $wordLower . '.';
        $textLength = $wordLength + 2;
        $patternLength = $this->patterns->maxLength();

        if ($patternLength > $textLength) {
            $patternLength = $textLength;
        }

        $scores = [];

        $end = $textLength - $this->minHyphenRight;
        for ($start = 0; $start < $end; $start++) {
            $maxLength = $start + $patternLength;
            if ($textLength - $start < $maxLength) {
                $maxLength = $textLength - $start;
            }

            for ($len = 1; $len <= $maxLength; $len++) {
                $subword = \mb_substr($text, $start, $len);
                $weights = $this->patterns->getWeights($subword);

                if ($weights === null) {
                    continue;
                }

                $weightsLength = \strlen($weights);
                for ($offset = 0; $offset < $weightsLength; $offset++) {
                    $score = (int) $weights[$offset];
                    if (!isset($scores[$start + $offset]) || $score > $scores[$start + $offset]) {
                        $scores[$start + $offset] = $score;
                    }
                }
            }
        }

        $parts = [];
        $part = \mb_substr($word, 0, $this->minHyphenLeft);
        $breakEnd = $textLength - $this->minHyphenRight;

        for ($i = $this->minHyphenLeft + 1; $i < $breakEnd; $i++) {
            if (isset($scores[$i])) {
                $score = $scores[$i];
                if (($score & 1) !== 0) {
                    $parts[] = $part;
                    $part = '';
                }
            }
            $part .= \mb_substr($word, $i - 1, 1);
        }

        for ($i = $breakEnd; $i < $textLength - 1; $i++) {
            $part .= \mb_substr($word, $i - 1, 1);
        }

        if ($part !== '') {
            $parts[] = $part;
        }

        return $parts;
    }
}

