<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Hyphenation\Source;

use GlobusStudio\ReadSight\Hyphenation\HyphenationExceptionsCollection;
use GlobusStudio\ReadSight\Hyphenation\PatternsCollection;

interface PatternSource
{
    /** @return array{patterns: PatternsCollection, exceptions: HyphenationExceptionsCollection, maxPatternLength: int} */
    public function load(): array;
}
