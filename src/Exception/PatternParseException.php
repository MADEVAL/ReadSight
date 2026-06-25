<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Exception;

final class PatternParseException extends ReadabilityEngineException
{
    public static function withLine(string $line, int $lineNumber, string $filePath): self
    {
        return new self(\sprintf(
            'Failed to parse pattern at line %d in file "%s": "%s"',
            $lineNumber,
            $filePath,
            $line,
        ));
    }
}
