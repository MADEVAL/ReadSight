<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Text;

use GlobusStudio\ReadSight\Language\Language;

final readonly class TextSplitter
{
    public function __construct(
        private Language $language,
    ) {
    }

    /** @return list<string> */
    public function splitWords(string $text): array
    {
        $text = \trim($text);

        if ($text === '') {
            return [];
        }

        $words = \mb_split($this->language->wordSplitPattern, $text);
        if ($words === false) {
            return [];
        }

        return \array_values(\array_filter($words, static fn(string $w): bool => $w !== ''));
    }

    /** @return list<string> */
    public function splitSentences(string $text): array
    {
        $text = \trim($text);

        if ($text === '') {
            return [];
        }

        $pattern = '/' . $this->language->sentenceBoundaryPattern . '/u';
        $parts = \preg_split($pattern, $text, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            return [];
        }

        return \array_map('trim', $parts);
    }

    public function countLetters(string $text): int
    {
        $text = \trim($text);

        if ($text === '') {
            return 0;
        }

        $pattern = '/' . $this->language->letterPattern . '/u';
        $count = \preg_match_all($pattern, $text);
        if ($count === false) {
            return 0;
        }

        return $count;
    }

    public function countWords(string $text): int
    {
        return \count($this->splitWords($text));
    }

    public function countSentences(string $text): int
    {
        $text = \trim($text);

        if ($text === '') {
            return 0;
        }

        $pattern = '/' . $this->language->sentenceBoundaryPattern . '/u';
        $count = \preg_match_all($pattern, $text);
        if ($count === false) {
            return 0;
        }

        return $count;
    }

    public function countLongWords(string $text, int $threshold): int
    {
        $words = $this->splitWords($text);
        $count = 0;

        foreach ($words as $word) {
            if ($this->countLetters($word) > $threshold) {
                $count++;
            }
        }

        return $count;
    }
}
