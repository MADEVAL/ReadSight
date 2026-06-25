<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class Gulpease implements Formula
{
    public function name(): string
    {
        return 'gulpease';
    }

    public function description(): string
    {
        return 'Gulpease Index - Italian readability formula. Uses letter count instead of syllables.';
    }

    /** @return list<string> */
    public function supportedLanguages(): array
    {
        return ['it'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        $wordCount = $stats->wordCount > 0 ? $stats->wordCount : 1;

        $score = 89.0 + (300.0 * $stats->sentenceCount - 10.0 * $stats->letterCount) / $wordCount;

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($score, 1),
            gradeLevel: null,
            interpretation: $this->interpret($score),
            gradeLabel: null,
            inputs: [
                'letterCount' => $stats->letterCount,
                'wordCount' => $stats->wordCount,
                'sentenceCount' => $stats->sentenceCount,
            ],
        );
    }

    private function interpret(float $score): string
    {
        return match (true) {
            $score >= 80.0 => 'Easy for elementary school',
            $score >= 60.0 => 'Easy for middle school',
            $score >= 40.0 => 'Easy for high school',
            default => 'Difficult for high school',
        };
    }
}
