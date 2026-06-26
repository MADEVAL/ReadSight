<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Language;

final class LanguageCode
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $this->value = self::normalize($value);
    }

    public static function normalize(string $code): string
    {
        return \mb_strtolower(\trim($code));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
