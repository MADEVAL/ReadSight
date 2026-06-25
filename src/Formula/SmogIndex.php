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
        return 'SMOG Index - Simple Measure of Gobbledygook. Estimates years of education needed.';
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
            interpretation: $this->interpret($score),
            gradeLabel: null,
            inputs: [
                'polysyllableCount' => $stats->polysyllableCount,
                'sentenceCount' => $stats->sentenceCount,
            ],
        );
    }

    private function interpret(float $score): string
    {
        return match (true) {
            $score <= 1.0 => 'Kindergarten',
            $score <= 2.0 => '1st Grade',
            $score <= 3.0 => '2nd Grade',
            $score <= 4.0 => '3rd Grade',
            $score <= 5.0 => '4th Grade',
            $score <= 6.0 => '5th Grade',
            $score <= 7.0 => '6th Grade',
            $score <= 8.0 => '7th Grade',
            $score <= 9.0 => '8th Grade',
            $score <= 10.0 => '9th Grade',
            $score <= 11.0 => '10th Grade',
            $score <= 12.0 => '11th Grade',
            $score <= 13.0 => '12th Grade',
            $score <= 16.0 => 'College',
            default => 'Graduate',
        };
    }
}
