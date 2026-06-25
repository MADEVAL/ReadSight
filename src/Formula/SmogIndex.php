<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class SmogIndex implements Formula
{
    public function name(): string
    {
        return 'smog';
    }

    public function description(): string
    {
        return 'SMOG Index — Simple Measure of Gobbledygook. Estimates years of education needed.';
    }

    /** @return list<string> */
    public function supportedLanguages(): array
    {
        return ['*'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        $sentenceCount = $stats->sentenceCount > 0 ? $stats->sentenceCount : 1;

        $score = 1.0430 * \sqrt($stats->polysyllableCount * (30.0 / $sentenceCount)) + 3.1291;

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($score, 1),
            gradeLevel: \min(\max(\round($score, 1), 0.0), 18.0),
            interpretation: '',
            gradeLabel: null,
            inputs: [
                'polysyllableCount' => $stats->polysyllableCount,
                'sentenceCount' => $stats->sentenceCount,
            ],
        );
    }
}

