<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class GutierrezPolini implements Formula
{
    public function name(): string
    {
        return 'gutierrez_polini';
    }

    public function description(): string
    {
        return 'Gutierrez de Polini Understandability Index — Spanish readability for elementary education.';
    }

    /** @return list<string> */
    public function supportedLanguages(): array
    {
        return ['es'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        $wordCount = $stats->wordCount > 0 ? $stats->wordCount : 1;

        $score = 95.2 - 9.7 * ($stats->letterCount / $wordCount) - 0.35 * $stats->averageWordsPerSentence;

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($score, 1),
            gradeLevel: null,
            interpretation: $this->interpret($score),
            gradeLabel: null,
            inputs: [
                'lettersPerWord' => \round($stats->letterCount / $wordCount, 2),
                'wordsPerSentence' => \round($stats->averageWordsPerSentence, 2),
            ],
        );
    }

    private function interpret(float $score): string
    {
        return match (true) {
            $score >= 80.0 => 'Very Easy',
            $score >= 70.0 => 'Easy',
            $score >= 50.0 => 'Standard',
            $score >= 30.0 => 'Difficult',
            default => 'Very Difficult',
        };
    }
}
