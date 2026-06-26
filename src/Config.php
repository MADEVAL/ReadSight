<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight;

final readonly class Config
{
    public function __construct(
        public string $patternsDir,
        public string $languagesDir,
        public string $cacheDir,
    ) {
    }

    public static function default(): self
    {
        return new self(
            patternsDir: __DIR__ . '/../data/patterns',
            languagesDir: __DIR__ . '/../data/languages',
            cacheDir: __DIR__ . '/../cache',
        );
    }
}
