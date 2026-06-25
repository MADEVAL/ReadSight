<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

final readonly class FleschReadingEase implements Formula
{
    public function name(): string
    {
        return 'flesch_reading_ease';
    }

    public function description(): string
    {
        return 'Flesch Reading Ease — measures text readability on a 0–100 scale (higher = easier). Coefficients vary by language.';
    }

    /** @return list<string> */
    public function supportedLanguages(): array
    {
        return ['en-us', 'en-gb', 'de-1996', 'de-1901', 'de-ch-1901', 'ru', 'es', 'it', 'fr', 'nl', 'pt', 'tr'];
    }

    public function calculate(TextStatistics $stats, Language $language): FormulaResult
    {
        $config = $language->getFormulaConfig($this->name()) ?? [];

        $base = 206.835;
        $asl = 1.015;
        $asw = 84.6;

        if (isset($config['base']) && is_numeric($config['base'])) {
            $base = (float) $config['base'];
        }
        if (isset($config['aslMult']) && is_numeric($config['aslMult'])) {
            $asl = (float) $config['aslMult'];
        }
        if (isset($config['aswMult']) && is_numeric($config['aswMult'])) {
            $asw = (float) $config['aswMult'];
        }

        $score = $base - $asl * $stats->averageWordsPerSentence - $asw * $stats->averageSyllablesPerWord;

        return new FormulaResult(
            formulaName: $this->name(),
            languageCode: $language->code,
            score: \round($score, 1),
            gradeLevel: null,
            interpretation: '',
            gradeLabel: $this->interpret($score),
            inputs: [
                'asl' => $stats->averageWordsPerSentence,
                'asw' => $stats->averageSyllablesPerWord,
            ],
        );
    }

    private function interpret(float $score): string
    {
        return match (true) {
            $score >= 90.0 => 'Very Easy',
            $score >= 80.0 => 'Easy',
            $score >= 70.0 => 'Fairly Easy',
            $score >= 60.0 => 'Standard',
            $score >= 50.0 => 'Fairly Hard',
            $score >= 30.0 => 'Hard',
            default => 'Very Hard',
        };
    }
}

