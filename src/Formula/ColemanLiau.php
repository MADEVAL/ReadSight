<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class ColemanLiau implements Formula
{
    public function name(): string
    {
        return 'coleman_liau';
    }

    public function description(): string
    {
        return 'Coleman-Liau Index - character-based readability formula (no syllable counting needed).';
    }

    /** @return list<string> */
    public function supportedLanguages(): array
    {
        return ['*'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        $wordCount = $stats->wordCount > 0 ? $stats->wordCount : 1;
        $sentenceCount = $stats->sentenceCount > 0 ? $stats->sentenceCount : 1;

        $L = ($stats->letterCount / $wordCount) * 100.0;
        $S = ($sentenceCount / $wordCount) * 100.0;

        $score = 0.0588 * $L - 0.296 * $S - 15.8;

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($score, 1),
            gradeLevel: \min(\max(\round($score, 1), 0.0), 18.0),
            interpretation: $this->interpret($score),
            gradeLabel: null,
            inputs: [
                'L' => \round($L, 2),
                'S' => \round($S, 2),
                'letterCount' => $stats->letterCount,
                'wordCount' => $stats->wordCount,
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
