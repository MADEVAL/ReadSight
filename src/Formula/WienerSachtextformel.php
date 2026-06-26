<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class WienerSachtextformel implements Formula
{
    public function name(): string
    {
        return 'wiener_sachtextformel';
    }

    public function description(): string
    {
        return 'Wiener Sachtextformel - German readability formula with 4 variants. Returns school grade level.';
    }

    /** @return list<string> */
    public function supportedLanguages(): array
    {
        return ['de-1996', 'de-1901', 'de-ch-1901'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        return $this->calculateVariant($stats, $language, 1);
    }

    /** @return array{score: float, gradeLevel: float, inputs: array<string, float|int>} */
    private function compute(int $variant, TextStatistics $stats): array
    {
        $wordCount = $stats->wordCount > 0 ? $stats->wordCount : 1;

        $ms = ($stats->polysyllableCount / $wordCount) * 100.0;
        $sl = $stats->averageWordsPerSentence;
        $iw = $this->longWordPct($stats, 6);
        $es = 0.0;

        if ($variant === 1) {
            $es = $this->oneSyllablePct($stats);
            $score = 0.1935 * $ms + 0.1672 * $sl + 0.1297 * $iw - 0.0327 * $es - 0.875;
        } elseif ($variant === 2) {
            $score = 0.2007 * $ms + 0.1682 * $sl + 0.1373 * $iw - 2.779;
        } elseif ($variant === 3) {
            $score = 0.2963 * $ms + 0.1905 * $sl - 1.1144;
        } elseif ($variant === 4) {
            $score = 0.2744 * $ms + 0.2656 * $sl - 1.693;
        } else {
            throw new \InvalidArgumentException(\sprintf(
                'Wiener Sachtextformel variant must be 1-4, got %d.',
                $variant,
            ));
        }

        $gradeLevel = \min(\max($score, 4.0), 15.0);

        return [
            'score' => \round($score, 1),
            'gradeLevel' => $gradeLevel,
            'inputs' => \compact('ms', 'sl', 'iw', 'es', 'variant'),
        ];
    }

    public function calculateVariant(TextStatistics $stats, Language $language, int $variant): FormulaResult
    {
        $data = $this->compute($variant, $stats);

        return new FormulaResult(
            formulaName: $this->name() . '_' . $variant,
            languageCode: $language->code,
            score: $data['score'],
            gradeLevel: $data['gradeLevel'],
            interpretation: $this->interpret($data['score']),
            /** @var array<string, float|int> $data['inputs'] */
            inputs: $data['inputs'],
        );
    }

    private function longWordPct(TextStatistics $stats, int $threshold): float
    {
        return $stats->wordCount > 0
            ? ($stats->longWordCount / $stats->wordCount) * 100.0
            : 0.0;
    }

    private function oneSyllablePct(TextStatistics $stats): float
    {
        $oneSyllable = $stats->syllableHistogram[1] ?? 0;

        return $stats->wordCount > 0
            ? ($oneSyllable / $stats->wordCount) * 100.0
            : 0.0;
    }

    private function interpret(float $score): string
    {
        return match (true) {
            $score < 5.0 => 'Very Easy',
            $score < 7.0 => 'Easy',
            $score < 9.0 => 'Standard',
            $score < 11.0 => 'Fairly Hard',
            $score < 13.0 => 'Hard',
            default => 'Very Hard',
        };
    }
}
