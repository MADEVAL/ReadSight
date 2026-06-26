<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class FernandezHuerta implements Formula
{
    public function name(): string
    {
        return 'fernandez_huerta';
    }

    public function description(): string
    {
        return 'Fernandez-Huerta - Spanish adaptation of Flesch Reading Ease.';
    }

    /** @return list<string> */
    public function supportedLanguages(): array
    {
        return ['es'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        $score = 206.84 - 1.02 * $stats->averageWordsPerSentence - 60.0 * $stats->averageSyllablesPerWord;

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($score, 1),
            gradeLevel: null,
            interpretation: $this->interpret($score),
            inputs: [
                'asl' => $stats->averageWordsPerSentence,
                'asw' => $stats->averageSyllablesPerWord,
            ],
        );
    }

    private function interpret(float $score): string
    {
        return match (true) {
            $score >= 90.0 => 'Very Easy',
            $score >= 80.0 => 'Easy',
            $score >= 70.0 => 'Fairly Easy',
            $score >= 60.0 => 'Standard',
            $score >= 50.0 => 'Fairly Difficult',
            $score >= 30.0 => 'Difficult',
            default => 'Very Difficult',
        };
    }
}
