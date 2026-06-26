<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Hyphenation;

final class HyphenationOverride
{
    public function __construct(
        public readonly string $word,
        public readonly string $hyphenated,
    ) {
    }
}
