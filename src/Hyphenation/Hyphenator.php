<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Hyphenation;

interface Hyphenator
{
    /**
     * Split a word into syllables.
     *
     * @param string $word The word to split.
     * @return list<string> Array of syllable parts.
     */
    public function hyphenate(string $word): array;

    /**
     * Count the number of syllables in a word.
     */
    public function countSyllables(string $word): int;
}
