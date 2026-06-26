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
            if ($counter instanceof HeuristicSyllableCounter && $counter->hasWord($word)) {
                return $counter->countSyllables($word);
            }
        }

        $chain = $this->chain;
        $last = \end($chain);

        return $last instanceof SyllableCounter ? $last->countSyllables($word) : 1;
    }

    /** @return list<string> */
    public function splitSyllables(string $word): array
    {
        foreach ($this->chain as $counter) {
            if ($counter instanceof HeuristicSyllableCounter && $counter->hasWord($word)) {
                return $counter->splitSyllables($word);
            }
        }

        $chain = $this->chain;
        $last = \end($chain);

        return $last instanceof SyllableCounter ? $last->splitSyllables($word) : [$word];
    }
}
