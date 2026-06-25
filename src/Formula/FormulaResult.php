<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

final readonly class FormulaResult
{
    /** @param array<string, float|int> $inputs */
    public function __construct(
        public string $formulaName,
        public string $languageCode,
        public float $score,
        public ?float $gradeLevel,
        public string $interpretation,
        public ?string $gradeLabel,
        public array $inputs,
    ) {}
}

