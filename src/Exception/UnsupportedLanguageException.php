<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Exception;

final class UnsupportedLanguageException extends ReadabilityEngineException
{
    public static function withCode(string $languageCode): self
    {
        return new self(\sprintf('Language "%s" is not supported.', $languageCode));
    }
}
