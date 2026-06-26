<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Syllable;

final readonly class HeuristicSyllableCounter implements SyllableCounter
{
    /** @var array<string, int> */
    private array $problemWords;

    /** @var list<string> */
    private array $subtractPatterns;

    /** @var list<string> */
    private array $addPatterns;

    /** @var array<string, int> */
    private array $prefixes;

    /** @var array<string, int> */
    private array $suffixes;

    private string $vowelChars;

    /**
     * @param array<string, mixed>|null $config
     */
    public function __construct(
        private ?array $config,
    ) {
        /** @var array<string, int> */
        $problemWords = $config['problemWords'] ?? [];
        $this->problemWords = $problemWords;

        /** @var list<string> */
        $subtractPatterns = $config['subtractPatterns'] ?? [];
        $this->subtractPatterns = $subtractPatterns;

        /** @var list<string> */
        $addPatterns = $config['addPatterns'] ?? [];
        $this->addPatterns = $addPatterns;

        /** @var array<string, int> */
        $prefixes = $config['prefixes'] ?? [];
        $this->prefixes = $prefixes;

        /** @var array<string, int> */
        $suffixes = $config['suffixes'] ?? [];
        $this->suffixes = $suffixes;

        $vowelRaw = $config['vowelPattern'] ?? null;
        $vowelPattern = \is_string($vowelRaw) ? $vowelRaw : '[aeiouy]';
        $this->vowelChars = \trim($vowelPattern, '[]');
    }

    public function countSyllables(string $word): int
    {
        $word = \trim($word);

        if ($word === '') {
            return 0;
        }

        $lower = \mb_strtolower($word);

        if (isset($this->problemWords[$lower])) {
            return $this->problemWords[$lower];
        }

        $cleanRaw = \preg_replace('/[^a-z]/', '', $lower);
        if (!\is_string($cleanRaw) || $cleanRaw === '') {
            return 1;
        }

        $clean = $cleanRaw;
        $affixSyllables = 0;

        foreach ($this->prefixes as $prefix => $sylCount) {
            if (\str_starts_with($clean, $prefix)) {
                $clean = \substr($clean, \strlen($prefix));
                $affixSyllables += $sylCount;
            }
        }

        foreach ($this->suffixes as $suffix => $sylCount) {
            if (\str_ends_with($clean, $suffix)) {
                $clean = \substr($clean, 0, -\strlen($suffix));
                $affixSyllables += $sylCount;
            }
        }

        $vowelParts = \preg_split('/[^' . $this->vowelChars . ']+/', $clean);
        $vowelRunCount = 0;
        if (\is_array($vowelParts)) {
            foreach ($vowelParts as $part) {
                if ($part !== '') {
                    $vowelRunCount++;
                }
            }
        }

        $count = $vowelRunCount + $affixSyllables;

        foreach ($this->subtractPatterns as $pattern) {
            $matchCount = \preg_match('/' . $pattern . '/', $clean);
            if ($matchCount !== false) {
                $count -= $matchCount;
            }
        }

        foreach ($this->addPatterns as $pattern) {
            $matchCount = \preg_match('/' . $pattern . '/', $clean);
            if ($matchCount !== false) {
                $count += $matchCount;
            }
        }

        return \max((int) $count, 1);
    }

    public function hasRules(): bool
    {
        return $this->config !== null
            && ($this->problemWords !== [] || $this->subtractPatterns !== [] || $this->addPatterns !== []);
    }
}
