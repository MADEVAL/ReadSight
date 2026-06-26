<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Language;

use GlobusStudio\ReadSight\Exception\UnsupportedLanguageException;

final class JsonLanguageRepository implements LanguageRepository
{
    /** @var array<string, Language> */
    private array $cache = [];

    public function __construct(
        private readonly string $languagesDir,
    ) {
    }

    public function find(string $languageCode): Language
    {
        $normalized = LanguageCode::normalize($languageCode);

        if (isset($this->cache[$normalized])) {
            return $this->cache[$normalized];
        }

        $filePath = $this->languagesDir . '/' . $normalized . '.json';

        if (!\file_exists($filePath)) {
            throw UnsupportedLanguageException::withCode($languageCode);
        }

        $json = \file_get_contents($filePath);
        if ($json === false) {
            throw UnsupportedLanguageException::withCode($languageCode);
        }

        /** @var array{
         *     code: string,
         *     name: string,
         *     nativeName: string,
         *     script: string,
         *     hyphenMins: array{left: int, right: int},
         *     letterPattern: string,
         *     wordSplitPattern: string,
         *     sentenceBoundaryPattern: string,
         *     formulas?: array<string, array<string, mixed>>
         * } $data
         */
        $data = \json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        $language = new Language($data);
        $this->cache[$normalized] = $language;

        return $language;
    }

    /** @return list<string> */
    public function listCodes(): array
    {
        $codes = [];
        $pattern = $this->languagesDir . '/*.json';

        $files = \glob($pattern);
        if ($files === false) {
            return [];
        }

        foreach ($files as $file) {
            $codes[] = \basename($file, '.json');
        }

        \sort($codes);

        return $codes;
    }

    public function exists(string $languageCode): bool
    {
        $normalized = LanguageCode::normalize($languageCode);

        if (isset($this->cache[$normalized])) {
            return true;
        }

        return \file_exists($this->languagesDir . '/' . $normalized . '.json');
    }
}
