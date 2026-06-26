<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class AutomatedReadabilityIndex implements Formula
{
    public function name(): string
    {
        return 'ari';
    }

    public function description(): string
    {
        return 'Automated Readability Index - character-based formula. Works for all alphabetic languages.';
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

        $score = 4.71 * ($stats->letterCount / $wordCount) + 0.5 * ($wordCount / $sentenceCount) - 21.43;

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($score, 1),
            gradeLevel: \min(\max(\round($score, 1), 0.0), 18.0),
            interpretation: $this->interpret($score),
            inputs: [
                'charsPerWord' => \round($stats->letterCount / $wordCount, 2),
                'wordsPerSentence' => \round($wordCount / $sentenceCount, 2),
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
