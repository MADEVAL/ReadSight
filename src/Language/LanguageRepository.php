<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Language;

interface LanguageRepository
{
    /** @throws \GlobusStudio\ReadSight\Exception\UnsupportedLanguageException */
    public function find(string $languageCode): Language;

    /** @return list<string> */
    public function listCodes(): array;

    public function exists(string $languageCode): bool;
}
