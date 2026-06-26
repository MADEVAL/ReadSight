<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Syllable;

interface SyllableCounter
{
    public function countSyllables(string $word): int;

    /** @return list<string> */
    public function splitSyllables(string $word): array;
}
