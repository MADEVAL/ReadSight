<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Hyphenation\Source;

use GlobusStudio\ReadSight\Exception\PatternFileNotFoundException;
use GlobusStudio\ReadSight\Exception\PatternParseException;
use GlobusStudio\ReadSight\Hyphenation\HyphenationOverride;
use GlobusStudio\ReadSight\Hyphenation\HyphenationExceptionsCollection;
use GlobusStudio\ReadSight\Hyphenation\Pattern;
use GlobusStudio\ReadSight\Hyphenation\PatternsCollection;

final readonly class TexSource implements PatternSource
{
    public function __construct(
        private string $texFilePath,
    ) {
    }

    public function load(): array
    {
        if (!\file_exists($this->texFilePath)) {
            throw PatternFileNotFoundException::forFile($this->texFilePath);
        }

        $lines = \file($this->texFilePath);
        if ($lines === false) {
            throw PatternFileNotFoundException::forFile($this->texFilePath);
        }

        $patterns = new PatternsCollection();
        $exceptions = new HyphenationExceptionsCollection();
        $lineNumber = 0;

        $command = null;
        $inBraces = false;

        foreach ($lines as $line) {
            $lineNumber++;
            $offset = 0;
            $strlen = \strlen($line);

            while ($offset < $strlen) {
                $char = $line[$offset];

                if ($char === '%' && !$inBraces) {
                    break;
                }

                if ($char === '\\' && !$inBraces) {
                    if (\preg_match('/^\\\\([a-zA-Z]+)/', \substr($line, $offset), $m) === 1) {
                        $command = $m[1];
                        $offset += \strlen($m[0]);
                        continue;
                    }
                    $offset++;
                    continue;
                }

                if ($char === '{') {
                    if ($command !== null) {
                        $inBraces = true;
                    }
                    $offset++;
                    continue;
                }

                if ($char === '}' && $inBraces) {
                    $inBraces = false;
                    $command = null;
                    $offset++;
                    continue;
                }

                if ($inBraces) {
                    if ($command === 'patterns') {
                        if (\preg_match('/^(\S+)/u', \substr($line, $offset), $m) === 1) {
                            $token = $m[0];
                            $pattern = $this->parsePatternToken($token, $lineNumber);
                            if ($pattern !== null) {
                                $patterns->add($pattern);
                            }
                            $offset += \strlen($m[0]);
                            continue;
                        }
                    } elseif ($command === 'hyphenation') {
                        if (\preg_match('/^(\S+)/u', \substr($line, $offset), $m) === 1) {
                            $token = $m[0];
                            $word = \str_replace('-', '', $token);
                            $word = \mb_strtolower($word);
                            $hyphenated = \mb_strtolower($token);
                            $exceptions->add(new HyphenationOverride($word, $hyphenated));
                            $offset += \strlen($m[0]);
                            continue;
                        }
                    }
                }

                $offset++;
            }
        }

        return [
            'patterns' => $patterns,
            'exceptions' => $exceptions,
            'maxPatternLength' => $patterns->maxLength(),
        ];
    }

    private function parsePatternToken(string $token, int $lineNumber): ?Pattern
    {
        $chars = [];
        $numbers = '';
        $expectNumber = true;
        $hasDigit = false;

        $segments = \preg_split('/(?<!^)(?!$)/u', $token);
        if ($segments === false) {
            throw PatternParseException::withLine($token, $lineNumber, $this->texFilePath);
        }

        foreach ($segments as $char) {
            if (\is_numeric($char)) {
                $numbers .= $char;
                $hasDigit = true;
                $expectNumber = false;
            } else {
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
}
