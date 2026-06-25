<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Hyphenation;

final class HyphenationExceptionsCollection
{
    /** @var array<string, string> Word (lowercased, no hyphens) => hyphenated form (with hyphens) */
    private array $exceptions = [];

    public function add(HyphenationException $exception): void
    {
        $this->exceptions[$exception->word] = $exception->hyphenated;
    }

    public function has(string $word): bool
    {
        return isset($this->exceptions[$word]);
    }

    public function get(string $word): ?string
    {
        return $this->exceptions[$word] ?? null;
    }

    public function count(): int
    {
        return \count($this->exceptions);
    }

    public function isEmpty(): bool
    {
        return $this->exceptions === [];
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->exceptions;
    }
}
