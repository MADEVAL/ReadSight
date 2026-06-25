<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Hyphenation\Cache;

use GlobusStudio\ReadSight\Hyphenation\HyphenationException;
use GlobusStudio\ReadSight\Hyphenation\HyphenationExceptionsCollection;
use GlobusStudio\ReadSight\Hyphenation\Pattern;
use GlobusStudio\ReadSight\Hyphenation\PatternsCollection;

final readonly class JsonPatternCache implements PatternCache
{
    private const string CACHE_VERSION = '1.0';

    public function __construct(
        private string $cacheDir,
    ) {}

    public function has(string $languageCode): bool
    {
        return \file_exists($this->getFilePath($languageCode));
    }

    /** @return array{patterns: PatternsCollection, exceptions: HyphenationExceptionsCollection, maxPatternLength: int}|null */
    public function get(string $languageCode): ?array
    {
        $filePath = $this->getFilePath($languageCode);

        if (!\file_exists($filePath)) {
            return null;
        }

        $json = \file_get_contents($filePath);
        if ($json === false) {
            return null;
        }

        /** @var array{version: string, patterns: list<array{chars: list<string>, weights: list<int>}>, exceptions: array<string, string>, maxPatternLength: int}|null $data */
        $data = \json_decode($json, true);

        if ($data === null || $data['version'] !== self::CACHE_VERSION) {
            return null;
        }

        $patterns = new PatternsCollection();
        foreach ($data['patterns'] as $p) {
            $patterns->add(new Pattern($p['chars'], $p['weights']));
        }

        $exceptions = new HyphenationExceptionsCollection();
        foreach ($data['exceptions'] as $word => $hyphenated) {
            $exceptions->add(new HyphenationException($word, $hyphenated));
        }

        return [
            'patterns' => $patterns,
            'exceptions' => $exceptions,
            'maxPatternLength' => $data['maxPatternLength'],
        ];
    }

    /** @param array{patterns: PatternsCollection, exceptions: HyphenationExceptionsCollection, maxPatternLength: int} $data */
    public function set(string $languageCode, array $data): void
    {
        $payload = [
            'version' => self::CACHE_VERSION,
            'patterns' => $this->serializePatterns($data['patterns']),
            'exceptions' => $data['exceptions']->all(),
            'maxPatternLength' => $data['maxPatternLength'],
        ];

        if (!\is_dir($this->cacheDir)) {
            \mkdir($this->cacheDir, 0777, true);
        }

        \file_put_contents(
            $this->getFilePath($languageCode),
            \json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );
    }

    public function clear(string $languageCode): void
    {
        $filePath = $this->getFilePath($languageCode);
        if (\file_exists($filePath)) {
            \unlink($filePath);
        }
    }

    public function clearAll(): void
    {
        $files = \glob($this->cacheDir . '/*.json');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            \unlink($file);
        }
    }

    private function getFilePath(string $languageCode): string
    {
        return $this->cacheDir . '/syllable.' . $languageCode . '.json';
    }

    /** @return list<array{chars: list<string>, weights: list<int>}> */
    private function serializePatterns(PatternsCollection $collection): array
    {
        $result = [];
        foreach ($collection->all() as $key => $weights) {
            $chars = \preg_split('/(?<!^)(?!$)/u', $key);
            if ($chars === false) {
                continue;
            }
            $weightValues = \array_map('intval', \str_split($weights));
            $result[] = [
                'chars' => $chars,
                'weights' => $weightValues,
            ];
        }

        return $result;
    }

}

