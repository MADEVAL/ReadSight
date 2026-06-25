<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Hyphenation\Source;

use GlobusStudio\ReadSight\Exception\PatternFileNotFoundException;
use GlobusStudio\ReadSight\Exception\PatternParseException;
use GlobusStudio\ReadSight\Hyphenation\HyphenationException;
use GlobusStudio\ReadSight\Hyphenation\HyphenationExceptionsCollection;
use GlobusStudio\ReadSight\Hyphenation\Pattern;
use GlobusStudio\ReadSight\Hyphenation\PatternsCollection;

final readonly class PatTxtSource implements PatternSource
{
    public function __construct(
        private string $patFilePath,
        private ?string $hypFilePath = null,
    ) {
    }

    /** @return array{patterns: PatternsCollection, exceptions: HyphenationExceptionsCollection, maxPatternLength: int} */
    public function load(): array
    {
        if (!\file_exists($this->patFilePath)) {
            throw PatternFileNotFoundException::forFile($this->patFilePath);
        }

        $patterns = $this->parsePatFile($this->patFilePath);
        $exceptions = $this->parseHypFile();

        return [
            'patterns' => $patterns,
            'exceptions' => $exceptions,
            'maxPatternLength' => $patterns->maxLength(),
        ];
    }

    private function parsePatFile(string $filePath): PatternsCollection
    {
        $collection = new PatternsCollection();
        $lines = \file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return $collection;
        }

        foreach ($lines as $lineNumber => $line) {
            $line = \trim($line);

            if ($line === '' || \str_starts_with($line, '%')) {
                continue;
            }

            $pattern = $this->parsePatLine($line, $lineNumber, $filePath);
            if ($pattern !== null) {
                $collection->add($pattern);
            }
        }

        return $collection;
    }

    private function parsePatLine(string $line, int $lineNumber, string $filePath): ?Pattern
    {
        $chars = [];
        $numbers = '';
        $expectNumber = true;
        $hasDigit = false;

        $segments = \preg_split('/(?<!^)(?!$)/u', $line);
        if ($segments === false) {
            throw PatternParseException::withLine($line, $lineNumber, $filePath);
        }

        foreach ($segments as $char) {
            if (\is_numeric($char)) {
                $numbers .= $char;
                $hasDigit = true;
                $expectNumber = false;
            } elseif ($char !== '.') {
                if ($expectNumber) {
                    $numbers .= '0';
                }
                $chars[] = $char;
                $expectNumber = true;
            }
        }

        if ($expectNumber) {
            $numbers .= '0';
        }

        if ($chars === [] || !$hasDigit) {
            return null;
        }

        $weights = \array_map('intval', \str_split($numbers));

        return new Pattern($chars, $weights);
    }

    private function parseHypFile(): HyphenationExceptionsCollection
    {
        $collection = new HyphenationExceptionsCollection();

        if ($this->hypFilePath === null || !\file_exists($this->hypFilePath)) {
            return $collection;
        }

        $lines = \file($this->hypFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return $collection;
        }

        foreach ($lines as $line) {
            $line = \trim($line);

            if ($line === '' || \str_starts_with($line, '%')) {
                continue;
            }

            $word = \str_replace('-', '', $line);
            $word = \mb_strtolower($word);
            $hyphenated = \mb_strtolower($line);

            $collection->add(new HyphenationException($word, $hyphenated));
        }

        return $collection;
    }
}
