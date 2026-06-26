<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Hyphenation;

final class PatternsCollection
{
    /**
     * Map of sub-pattern string to weights string.
     *
     * Key: the character sequence (e.g. "ach")
     * Value: the digit string representing weights (e.g. "004")
     *
     * @var array<string, string>
     */
    private array $patterns = [];

    private int $maxPatternLength = 0;

    public function add(Pattern $pattern): void
    {
        $key = \implode('', $pattern->chars);
        $weights = \implode('', $pattern->weights);
        $this->patterns[$key] = $weights;

        if ($pattern->length > $this->maxPatternLength) {
            $this->maxPatternLength = $pattern->length;
        }
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->patterns;
    }

    /** @param string $subword The sub-pattern to look up */
    public function getWeights(string $subword): ?string
    {
        return $this->patterns[$subword] ?? null;
    }

    public function count(): int
    {
        return \count($this->patterns);
    }

    public function maxLength(): int
    {
        return $this->maxPatternLength;
    }

    public function isEmpty(): bool
    {
        return $this->patterns === [];
    }
}
