<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Hyphenation;

final class Pattern
{
    public readonly int $length;

    /**
     * @param list<string> $chars
     * @param list<int> $weights
     */
    public function __construct(
        public readonly array $chars,
        public readonly array $weights,
    ) {
        $this->length = \count($chars);
    }

}
