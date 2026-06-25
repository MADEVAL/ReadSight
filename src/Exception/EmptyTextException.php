<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Exception;

final class EmptyTextException extends ReadabilityEngineException
{
    public static function create(): self
    {
        return new self('Text must contain at least one letter.');
    }
}

