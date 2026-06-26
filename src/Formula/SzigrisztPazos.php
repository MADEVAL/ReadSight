<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class SzigrisztPazos implements Formula
{
    public function name(): string
    {
        return 'szigriszt_pazos';
    }

    public function description(): string
    {
        return 'Szigriszt-Pazos Perspicuity Index - Spanish readability formula.';
    }

    /** @return list<string> */
    public function supportedLanguages(): array
    {
        return ['es'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        $wordCount = $stats->wordCount > 0 ? $stats->wordCount : 1;
        $P = $stats->averageWordsPerSentence;

        $syllablesPerWord = $stats->syllableCount / $wordCount;
        $syllablesPer100 = \round($syllablesPerWord * 100.0, 1);
        $score = \round(206.835 - 62.3 * $syllablesPerWord - $P, 1);

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: $score,
            gradeLevel: null,
            interpretation: $this->interpret($score),
            inputs: [
                'syllablesPer100' => $syllablesPer100,
                'wordsPerSentence' => \round($P, 1),
            ],
        );
    }

    private function interpret(float $score): string
    {
        return match (true) {
            $score >= 85.0 => 'Very Easy',
            $score >= 75.0 => 'Easy',
            $score >= 65.0 => 'Fairly Easy',
            $score >= 55.0 => 'Standard',
            $score >= 40.0 => 'Fairly Difficult',
            $score >= 30.0 => 'Difficult',
            default => 'Very Difficult',
        };
    }
}
