<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Hyphenation\Cache;

use GlobusStudio\ReadSight\Hyphenation\HyphenationExceptionsCollection;
use GlobusStudio\ReadSight\Hyphenation\PatternsCollection;

interface PatternCache
{
    public function has(string $languageCode): bool;

    /** @return array{patterns: PatternsCollection, exceptions: HyphenationExceptionsCollection, maxPatternLength: int}|null */
    public function get(string $languageCode): ?array;

    /** @param array{patterns: PatternsCollection, exceptions: HyphenationExceptionsCollection, maxPatternLength: int} $data */
    public function set(string $languageCode, array $data): void;

    public function clear(string $languageCode): void;

    public function clearAll(): void;
}

