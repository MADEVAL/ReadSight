<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class Crawford implements Formula
{
    public function name(): string
    {
        return 'crawford';
    }

    public function description(): string
    {
        return 'Crawford Formula - Spanish readability for elementary school texts. Returns years of schooling.';
    }

    /** @return list<string> */
    public function supportedLanguages(): array
    {
        return ['es'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        $wordCount = $stats->wordCount > 0 ? $stats->wordCount : 1;
        $sentenceCount = $stats->sentenceCount > 0 ? $stats->sentenceCount : 1;

        // Crawford (1989) is a Fry-type procedure: its two inputs are sentences
        // per 100 words (OP) and syllables per 100 words (SP).
        $sentencesPer100 = ($sentenceCount / $wordCount) * 100.0;
        $syllablesPer100 = ($stats->syllableCount / $wordCount) * 100.0;

        $score = -0.205 * $sentencesPer100 + 0.049 * $syllablesPer100 - 3.407;

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($score, 1),
            gradeLevel: null,
            interpretation: $this->interpret($score),
            inputs: [
                'sentencesPer100Words' => \round($sentencesPer100, 2),
                'syllablesPer100Words' => \round($syllablesPer100, 2),
            ],
        );
    }

    private function interpret(float $score): string
    {
        // Years of schooling: the more years a text needs, the harder it is.
        return match (true) {
            $score <= 3.0 => 'Very Easy',
            $score <= 5.0 => 'Easy',
            $score <= 7.0 => 'Standard',
            $score <= 9.0 => 'Difficult',
            default => 'Very Difficult',
        };
    }
}
