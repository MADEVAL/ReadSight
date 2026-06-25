<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Hyphenation;

final class HyphenationException
{
    public function __construct(
        public readonly string $word,
        public readonly string $hyphenated,
    ) {
    }
}
