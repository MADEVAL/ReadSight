<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Syllable;

interface SyllableCounter
{
    public function countSyllables(string $word): int;
}
