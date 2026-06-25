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
        return 'Szigriszt-Pazos Perspicuity Index — Spanish readability formula.';
    }

    /** @return list<string> */
    public function supportedLanguages(): array
    {
        return ['es'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        $wordCount = $stats->wordCount > 0 ? $stats->wordCount : 1;
        $S = ($stats->syllableCount / $wordCount) * 100.0;
        $P = $stats->averageWordsPerSentence;

        $score = 206.835 - 62.3 * $S / 100.0 - $P;

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($score, 1),
            gradeLevel: null,
            interpretation: $this->interpret($score),
            gradeLabel: null,
            inputs: [
                'syllablesPer100' => \round($S, 1),
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

