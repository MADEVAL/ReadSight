<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class FogPL implements Formula
{
    public function name(): string
    {
        return 'fog_pl';
    }

    public function description(): string
    {
        return 'FOG-PL - Polish adaptation of Gunning Fog Index.';
    }

    /** @return list<string> */
    public function supportedLanguages(): array
    {
        return ['pl'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        $wordCount = $stats->wordCount > 0 ? $stats->wordCount : 1;
        $sentenceCount = $stats->sentenceCount > 0 ? $stats->sentenceCount : 1;

        $hardWordsPct = ($stats->polysyllableCount / $wordCount) * 100.0;
        $asl = $wordCount / $sentenceCount;

        $score = 0.4 * ($asl + $hardWordsPct);

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($score, 1),
            gradeLevel: \min(\max(\round($score, 1), 0.0), 19.0),
            interpretation: $this->interpret($score),
            gradeLabel: null,
            inputs: [
                'asl' => \round($asl, 2),
                'hardWordsPct' => \round($hardWordsPct, 2),
                'polysyllableCount' => $stats->polysyllableCount,
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
            default => 'Very Hard',
        };
    }
}
