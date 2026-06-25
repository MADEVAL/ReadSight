<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class Lix implements Formula
{
    public function name(): string
    {
        return 'lix';
    }

    public function description(): string
    {
        return 'LIX (Läsbarhetsindex) — Scandinavian readability formula. Language-independent, uses letter count.';
    }

    /** @return list<string> */
    public function supportedLanguages(): array
    {
        return ['*'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        $config = $language->getFormulaConfig($this->name()) ?? [];
        $threshold = isset($config['longWordThreshold']) && \is_numeric($config['longWordThreshold'])
            ? (int) $config['longWordThreshold']
            : 6;

        $longWordPct = $stats->wordCount > 0
            ? ($stats->longWordCount / $stats->wordCount) * 100.0
            : 0.0;

        $score = $stats->averageWordsPerSentence + $longWordPct;

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($score, 2),
            gradeLevel: null,
            interpretation: $this->interpret($score),
            gradeLabel: $this->interpret($score),
            inputs: [
                'asl' => $stats->averageWordsPerSentence,
                'longWordPct' => \round($longWordPct, 2),
                'threshold' => $threshold,
                'longWordCount' => $stats->longWordCount,
                'wordCount' => $stats->wordCount,
            ],
        );
    }

    private function interpret(float $score): string
    {
        return match (true) {
            $score < 25.0 => 'Children\'s Books',
            $score < 30.0 => 'Simple Texts',
            $score < 40.0 => 'Normal / Fiction',
            $score < 50.0 => 'Factual Information',
            $score < 60.0 => 'Specialized Texts',
            default => 'Research / Advanced',
        };
    }
}

