<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Exception;

final class PatternFileNotFoundException extends ReadabilityEngineException
{
    public static function forFile(string $filePath): self
    {
        return new self(\sprintf('Pattern file not found: "%s".', $filePath));
    }
}
