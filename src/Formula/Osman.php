<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class Osman implements Formula
{
    public function name(): string
    {
        return 'osman';
    }

    public function description(): string
    {
        return 'OSMAN - Arabic readability formula combining Flesch and Fog adaptations.';
    }

    /** @return list<string> */
    public function supportedLanguages(): array
    {
        return ['ar'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        $wordCount = $stats->wordCount > 0 ? $stats->wordCount : 1;
        $sentenceCount = $stats->sentenceCount > 0 ? $stats->sentenceCount : 1;

        $asl = $wordCount / $sentenceCount;
        $avgLetters = $stats->letterCount / $wordCount;
        $hardWordsPct = ($stats->polysyllableCount / $wordCount) * 100.0;

        $score = 200.0 - 2.0 * $asl - 1.5 * $avgLetters - 0.4 * $hardWordsPct;

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($score, 1),
            gradeLevel: null,
            interpretation: $this->interpret($score),
            gradeLabel: null,
            inputs: [
                'asl' => \round($asl, 2),
                'avgLetters' => \round($avgLetters, 2),
                'hardWordsPct' => \round($hardWordsPct, 2),
            ],
        );
    }

    private function interpret(float $score): string
    {
        return match (true) {
            $score >= 90.0 => 'Very Easy',
            $score >= 70.0 => 'Easy',
            $score >= 50.0 => 'Standard',
            $score >= 30.0 => 'Difficult',
            default => 'Very Difficult',
        };
    }
}
