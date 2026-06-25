<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Exception;

final class UnsupportedFormulaException extends ReadabilityEngineException
{
    public static function forLanguage(string $formulaName, string $languageCode): self
    {
        return new self(\sprintf(
            'Formula "%s" is not supported for language "%s".',
            $formulaName,
            $languageCode,
        ));
    }
}

