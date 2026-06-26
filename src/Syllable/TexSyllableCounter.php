<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Syllable;

use GlobusStudio\ReadSight\Hyphenation\Hyphenator;

final readonly class TexSyllableCounter implements SyllableCounter
{
    public function __construct(
        private Hyphenator $hyphenator,
    ) {
    }

    public function countSyllables(string $word): int
    {
        return $this->hyphenator->countSyllables($word);
    }
}
