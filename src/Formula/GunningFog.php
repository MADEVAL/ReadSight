<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class GunningFog implements Formula
{
    public function name(): string
    {
        return 'gunning_fog';
    }

    public function description(): string
    {
        return 'Gunning Fog Index - estimates years of education needed to understand text.';
    }

    /** @return list<string> */
    public function supportedLanguages(): array
    {
        return ['*'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        $polysyllablePct = $stats->wordCount > 0
            ? ($stats->polysyllableCount / $stats->wordCount) * 100.0
            : 0.0;

        $score = 0.4 * ($stats->averageWordsPerSentence + $polysyllablePct);

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($score, 1),
            gradeLevel: \min(\max(\round($score, 1), 0.0), 19.0),
            interpretation: $this->interpret($score),
            inputs: [
                'asl' => $stats->averageWordsPerSentence,
                'polysyllablePct' => $polysyllablePct,
                'polysyllableCount' => $stats->polysyllableCount,
                'wordCount' => $stats->wordCount,
            ],
        );
    }

    private function interpret(float $score): string
    {
        return match (true) {
            $score < 6.0 => 'Very Easy',
            $score < 8.0 => 'Easy',
            $score < 12.0 => 'Standard',
            $score < 14.0 => 'Hard',
            $score < 17.0 => 'Very Hard',
            default => 'Extremely Hard',
        };
    }
}
