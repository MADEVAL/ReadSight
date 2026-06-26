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
            interpretation: GradeLevelInterpretation::forScore($score),
            inputs: [
                'charsPerWord' => \round($stats->letterCount / $wordCount, 2),
                'wordsPerSentence' => \round($wordCount / $sentenceCount, 2),
            ],
        );
    }
}
