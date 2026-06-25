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

    public function toString(): string
    {
        $result = '';

        foreach ($this->chars as $i => $char) {
            $weight = $this->weights[$i];
            if ($weight !== 0) {
                $result .= (string) $weight;
            }
            $result .= $char;
        }

        $lastWeight = $this->weights[\count($this->chars)];
        if ($lastWeight !== 0) {
            $result .= (string) $lastWeight;
        }

        return $result;
    }
}

