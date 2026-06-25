<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class Spache implements Formula
{
    public function name(): string
    {
        return 'spache';
    }

    public function description(): string
    {
        return 'Spache Readability Score — designed for primary-grade texts (K-4).';
    }

    public function supportedLanguages(): array
    {
        return ['en-us', 'en-gb'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        $difficultPct = $this->estimateDifficultPercentage($stats);
        $score = 0.121 * $stats->averageWordsPerSentence + 0.082 * $difficultPct + 0.659;

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($score, 1),
            gradeLevel: \min(\max(\round($score, 1), 0.0), 5.0),
            interpretation: $this->interpret($score),
            gradeLabel: null,
            inputs: [
                'averageWordsPerSentence' => $stats->averageWordsPerSentence,
                'difficultWordPct' => \round($difficultPct, 2),
            ],
        );
    }

    private function estimateDifficultPercentage(TextStatistics $stats): float
    {
        if ($stats->wordCount === 0) {
            return 0.0;
        }

        $easyWordCount = $stats->syllableHistogram[1] ?? 0;
        $difficultCount = $stats->wordCount - $easyWordCount;

        if ($difficultCount < 0) {
            $difficultCount = 0;
        }

        return ($difficultCount / $stats->wordCount) * 100.0;
    }

    private function interpret(float $score): string
    {
        return match (true) {
            $score <= 2.0 => '1st Grade',
            $score <= 2.5 => '2nd Grade',
            $score <= 3.0 => '3rd Grade',
            $score <= 3.5 => '4th Grade',
            default => 'Above 4th Grade',
        };
    }
}

