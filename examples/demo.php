<?php

declare(strict_types=1);

/**
 * ReadSight Demo - Multilingual Readability Analyzer
 *
 * Usage:
 *   php examples/demo.php                                              # analyze built-in sample texts
 *   php examples/demo.php --file=path/to/text.txt                      # analyze a custom file
 *   php examples/demo.php --file=path/to/text.txt --lang=ru            # analyze in a specific language
 *   php examples/demo.php --compare                                    # compare same text across languages
 */

require_once __DIR__ . '/../vendor/autoload.php';

use GlobusStudio\ReadSight\Engine;

// --- CLI argument parsing ---
$options = getopt('', ['file:', 'lang:', 'compare', 'help']);
if (isset($options['help'])) {
    echo <<<HELP
ReadSight Demo - Multilingual Readability Analyzer

USAGE:
  php examples/demo.php [options]

OPTIONS:
  --file=PATH      Analyze text from a file
  --lang=CODE      Force a specific language (default: en-us)
  --compare        Compare readability across 8 languages
  --help           Show this help

EXAMPLES:
  php examples/demo.php
  php examples/demo.php --file=my-essay.txt
  php examples/demo.php --file=my-essay.txt --lang=de-1996
  php examples/demo.php --compare

HELP;
    exit(0);
}

$compareMode = isset($options['compare']);

// --- Sample texts (public domain) ---
$samples = [
    'simple' => <<<TEXT
The cat sat on the mat. It was a nice day. The sun was bright and warm.
TEXT,
    'medium' => <<<TEXT
Reading is one of the most important skills a person can learn. Books open doors
to new worlds and ideas. They allow us to travel through time and space without
ever leaving our chair. A good book can change the way we think about life.
TEXT,
    'complex' => <<<TEXT
The epistemological foundations of postmodern deconstruction challenge the
presuppositional frameworks inherent in traditional hermeneutic methodologies.
Contemporary discourse analysis reveals the multifaceted nature of interpretive
paradigms across heterogeneous cultural contexts and interdisciplinary boundaries.
TEXT,
];

// --- Section divider ---
function section(string $title): void
{
    echo "\n" . str_repeat('━', 60) . "\n";
    echo '  ' . $title . "\n";
    echo str_repeat('━', 60) . "\n\n";
}

// --- Print formula result ---
function printResult(string $label, $result): void
{
    printf(
        "  %-26s  score: %8s  %s\n",
        $label . ':',
        number_format($result->score, 2, '.', ''),
        $result->interpretation ?: $result->gradeLabel ?: '',
    );
}

// --- Analyze a single text in one language ---
function analyzeSingle(Engine $engine, string $text): void
{
    // Syllable breakdown
    section('Syllable Analysis');
    $words = ['banana', 'character', 'communication', 'incredible', 'information', 'automatic', 'extraordinary', 'university', 'readability'];
    echo "  Word            Syllables  Hyphenation\n";
    echo "  ────            ─────────  ───────────\n";
    foreach ($words as $word) {
        $count = $engine->syllableCount($word);
        $parts = $engine->splitWord($word);
        printf("  %-16s  %-9d  %s\n", $word, $count, implode(' · ', $parts));
    }

    // Text statistics
    section('Text Statistics');
    $stats = $engine->analyze($text);

    $lines = [
        ['Letters', (string) $stats->letterCount],
        ['Words', (string) $stats->wordCount],
        ['Sentences', (string) $stats->sentenceCount],
        ['Syllables (total)', (string) $stats->syllableCount],
        ['Avg syllables/word', number_format($stats->averageSyllablesPerWord, 2, '.', '')],
        ['Avg words/sentence', number_format($stats->averageWordsPerSentence, 2, '.', '')],
        ['Polysyllables (>2)', (string) $stats->polysyllableCount],
    ];

    foreach ($lines as [$label, $value]) {
        printf("  %-22s %s\n", $label . ':', $value);
    }

    // Syllable histogram
    if ($stats->syllableHistogram !== []) {
        echo "\n  Syllable distribution:\n  ";
        $max = max($stats->syllableHistogram);
        foreach ($stats->syllableHistogram as $n => $count) {
            $bar = str_repeat('█', (int) round($count / $max * 20));
            printf("\n  %d-syl: %s %d", $n, $bar, $count);
        }
        echo "\n";
    }

    // Readability formulas
    section('Readability Formulas');
    $formulas = $engine->getSupportedFormulas();

    $orderedFormulas = [
        'flesch_reading_ease',
        'flesch_kincaid_grade_level',
        'gunning_fog',
        'smog',
        'coleman_liau',
        'ari',
        'lix',
        'wiener_sachtextformel',
        'gulpease',
        'fernandez_huerta',
        'szigriszt_pazos',
        'gutierrez_polini',
        'crawford',
        'fog_pl',
        'dale_chall',
        'spache',
        'osman',
    ];

    foreach ($orderedFormulas as $name) {
        if (!in_array($name, $formulas, true)) {
            continue;
        }

        try {
            $result = $engine->score($name, $text);
            $label = $result->formulaName;
            if ($result->gradeLevel !== null) {
                $label .= sprintf(' (grade %.1f)', $result->gradeLevel);
            }
            printResult($label, $result);
        } catch (\Throwable $e) {
            printf("  %-26s  ERROR: %s\n", $name . ':', $e->getMessage());
        }
    }
}

// --- Compare text across languages ---
function compareLanguages(string $text): void
{
    $languages = [
        'en-us' => 'English (US)',
        'de-1996' => 'German',
        'ru' => 'Russian',
        'es' => 'Spanish',
        'fr' => 'French',
        'it' => 'Italian',
        'nl' => 'Dutch',
        'pt' => 'Portuguese',
    ];

    section('Cross-Language Comparison');

    // Header
    printf("  %-18s", 'Language');
    printf("  %8s", 'Words');
    printf("  %8s", 'Syll.');
    printf("  %8s", 'ASW');
    printf("  %8s", 'ASL');
    printf("  %10s", 'FRE');
    printf("  %10s", 'G.Fog');
    printf("  %8s", 'LIX');
    echo "\n  " . str_repeat('─', 18);
    echo '  ' . str_repeat('─', 8);
    echo '  ' . str_repeat('─', 8);
    echo '  ' . str_repeat('─', 8);
    echo '  ' . str_repeat('─', 8);
    echo '  ' . str_repeat('─', 10);
    echo '  ' . str_repeat('─', 10);
    echo '  ' . str_repeat('─', 8);
    echo "\n";

    foreach ($languages as $code => $name) {
        try {
            $engine = new Engine($code);
            $stats = $engine->analyze($text);

            printf("  %-18s", $name);
            printf("  %8d", $stats->wordCount);
            printf("  %8d", $stats->syllableCount);
            printf("  %8.2f", $stats->averageSyllablesPerWord);
            printf("  %8.2f", $stats->averageWordsPerSentence);

            // Flesch Reading Ease (if available)
            try {
                $fre = $engine->fleschReadingEase($text);
                printf("  %10.1f", $fre->score);
            } catch (\Throwable) {
                printf("  %10s", '-');
            }

            // Gunning Fog
            try {
                $fog = $engine->gunningFog($text);
                printf("  %10.1f", $fog->score);
            } catch (\Throwable) {
                printf("  %10s", '-');
            }

            // LIX
            try {
                $lix = $engine->lix($text);
                printf("  %8.1f", $lix->score);
            } catch (\Throwable) {
                printf("  %8s", '-');
            }

            echo "\n";
        } catch (\Throwable $e) {
            printf("  %-18s  SKIP: %s\n", $name, $e->getMessage());
        }
    }

    echo "\n  ASW = Average Syllables per Word\n";
    echo "  ASL = Average Words per Sentence\n";
    echo "  FRE = Flesch Reading Ease (higher = easier, 0-100)\n";
    echo "  G.Fog = Gunning Fog Index (grade level)\n";
    echo "  LIX = Läsbarhetsindex (lower = easier)\n";
}

// --- Main ---
echo "\n";
echo "  ╔" . str_repeat('═', 56) . "╗\n";
echo "  ║  ReadSight - Multilingual Readability Engine Demo        ║\n";
echo "  ╚" . str_repeat('═', 56) . "╝\n";

// Select text
if (isset($options['file'])) {
    $filePath = $options['file'];
    if (!file_exists($filePath)) {
        echo "\n  Error: File not found - {$filePath}\n";
        exit(1);
    }
    $text = file_get_contents($filePath);
    if ($text === false || trim($text) === '') {
        echo "\n  Error: File is empty or could not be read\n";
        exit(1);
    }
    echo "\n  Source: {$filePath} (" . mb_strlen($text) . " chars)\n";
} else {
    $text = $samples['medium'];
    echo "\n  Source: built-in sample text\n";
}

echo '  Text:  "' . mb_substr(trim($text), 0, 80) . '..."' . "\n";

if ($compareMode) {
    compareLanguages($text);
} else {
    // Single language analysis
    $langCode = $options['lang'] ?? 'en-us';

    section('Engine Initialization');
    $startInit = microtime(true);
    $engine = new Engine($langCode);
    $initTime = (microtime(true) - $startInit) * 1000;

    $lang = $engine->getLanguage();
    printf(
        "  Language: %s (%s)\n",
        $lang->name,
        $lang->nativeName,
    );
    printf("  Script:   %s\n", $lang->script->name);
    printf("  Formulas: %d available\n", count($engine->getSupportedFormulas()));
    printf("  Init:     %.1f ms\n", $initTime);

    // Warm up cache
    $engine->syllableCount('test');

    // Analyze
    $start = microtime(true);
    analyzeSingle($engine, $text);
    $elapsed = (microtime(true) - $start) * 1000;

    echo str_repeat('─', 60) . "\n";
    printf("  Analysis completed in %.1f ms\n", $elapsed);
}

echo "\n";
