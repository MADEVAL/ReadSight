<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class FleschKincaidGradeLevel implements Formula
{
    public function name(): string
    {
        return 'flesch_kincaid_grade_level';
    }

    public function description(): string
    {
        return 'Flesch-Kincaid Grade Level — converts Reading Ease into a U.S. school grade level.';
    }

    /** @return list<string> */
    public function supportedLanguages(): array
    {
        return ['en-us', 'en-gb', 'de-1996', 'de-1901', 'de-ch-1901', 'ru', 'es', 'it', 'fr', 'nl', 'pt', 'tr'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        $score = 0.39 * $stats->averageWordsPerSentence + 11.8 * $stats->averageSyllablesPerWord - 15.59;

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($score, 1),
            gradeLevel: \min(\max(\round($score, 1), 0.0), 18.0),
            interpretation: $this->interpret($score),
            gradeLabel: null,
            inputs: [
                'asl' => $stats->averageWordsPerSentence,
                'asw' => $stats->averageSyllablesPerWord,
            ],
        );
    }

    private function interpret(float $score): string
    {
        return match (true) {
            $score <= 1.0 => '1st Grade',
            $score <= 2.0 => '2nd Grade',
            $score <= 3.0 => '3rd Grade',
            $score <= 4.0 => '4th Grade',
            $score <= 5.0 => '5th Grade',
            $score <= 6.0 => '6th Grade',
            $score <= 7.0 => '7th Grade',
            $score <= 8.0 => '8th Grade',
            $score <= 9.0 => '9th Grade',
            $score <= 10.0 => '10th Grade',
            $score <= 11.0 => '11th Grade',
            $score <= 12.0 => '12th Grade',
            $score <= 16.0 => 'College',
            default => 'Graduate',
        };
    }
}
