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
        return 'Coleman-Liau Index — character-based readability formula (no syllable counting needed).';
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
            interpretation: '',
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
}

