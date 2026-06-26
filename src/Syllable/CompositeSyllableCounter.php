<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Syllable;

final readonly class CompositeSyllableCounter implements SyllableCounter
{
    /** @param list<SyllableCounter> $chain */
    public function __construct(
        private array $chain,
    ) {
    }

    public function countSyllables(string $word): int
    {
        foreach ($this->chain as $counter) {
            $result = $counter->countSyllables($word);

            if ($counter instanceof HeuristicSyllableCounter && $counter->hasRules()) {
                return $result;
            }
        }

        return 1;
    }
}
