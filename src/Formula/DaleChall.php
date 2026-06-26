<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class DaleChall implements Formula
{
    public function name(): string
    {
        return 'dale_chall';
    }

    public function description(): string
    {
        return 'Dale-Chall Readability Score - estimates difficult words via syllable heuristic (1-syllable ≈ easy). NOTE: This is a simplified estimation, not the original 3000-word Dale list.';
    }

    public function supportedLanguages(): array
    {
        return ['en-us', 'en-gb'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        $difficultPct = $this->estimateDifficultPercentage($stats);
        $rawScore = 0.1579 * $difficultPct + 0.0496 * $stats->averageWordsPerSentence;
        $adjusted = $rawScore > 0.05 ? $rawScore + 3.6365 : $rawScore;

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($adjusted, 1),
            gradeLevel: null,
            interpretation: $this->interpret($adjusted),
            inputs: [
                'difficultWordPct' => \round($difficultPct, 1),
                'rawScore' => \round($rawScore, 4),
                'averageWordsPerSentence' => $stats->averageWordsPerSentence,
            ],
        );
    }

    private function estimateDifficultPercentage(TextStatistics $stats): float
    {
        return TextStatisticsHelper::estimateDifficultPercentage($stats);
    }

    private function interpret(float $score): string
    {
        return match (true) {
            $score <= 4.9 => '4th grade or below',
            $score <= 5.9 => '5th-6th grade',
            $score <= 6.9 => '7th-8th grade',
            $score <= 7.9 => '9th-10th grade',
            $score <= 8.9 => '11th-12th grade',
            $score <= 9.9 => 'College',
            default => 'Graduate',
        };
    }
}
