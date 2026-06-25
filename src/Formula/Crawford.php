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
        return 'Crawford Formula — Spanish readability for elementary school texts. Returns years of schooling.';
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

        $averageLetters = $stats->letterCount / $wordCount;
        $sentencesPer100 = ($sentenceCount / $wordCount) * 100.0;

        $score = -0.205 * $averageLetters + 0.049 * $sentencesPer100 - 3.407;

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($score, 1),
            gradeLevel: null,
            interpretation: $this->interpret($score),
            gradeLabel: null,
            inputs: [
                'avgLettersPerWord' => \round($averageLetters, 2),
                'sentencesPer100Words' => \round($sentencesPer100, 2),
            ],
        );
    }

    private function interpret(float $score): string
    {
        return match (true) {
            $score >= 9.0 => 'Very Easy',
            $score >= 7.0 => 'Easy',
            $score >= 5.0 => 'Standard',
            $score >= 3.0 => 'Difficult',
            default => 'Very Difficult',
        };
    }
}
