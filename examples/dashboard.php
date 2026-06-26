<?php

declare(strict_types=1);

/**
 * ReadSight Dashboard - side-by-side readability comparison
 *
 * Renders a single grid with all text metrics, a syllable histogram and every
 * readability formula available for the chosen language.
 *
 * Usage:
 *   php examples/dashboard.php                              # compare two built-in sample texts (en-us)
 *   php examples/dashboard.php --lang=de-1996               # same comparison in another language
 *   php examples/dashboard.php --file=path/to/text.txt      # analyze a single custom file
 *   php examples/dashboard.php --file=essay.txt --lang=ru   # custom file in a specific language
 */

require_once __DIR__ . '/../vendor/autoload.php';

use GlobusStudio\ReadSight\Engine;
use GlobusStudio\ReadSight\Text\TextStatistics;

$options = getopt('', ['file:', 'lang:', 'help']);

if (isset($options['help'])) {
    echo <<<HELP
    ReadSight Dashboard - side-by-side readability comparison

    USAGE:
      php examples/dashboard.php [options]

    OPTIONS:
      --file=PATH   Analyze a single text file (instead of the built-in samples)
      --lang=CODE   Language code (default: en-us)
      --help        Show this help

    HELP;
    exit(0);
}

$langCode = is_string($options['lang'] ?? null) ? $options['lang'] : 'en-us';
$engine = new Engine($langCode);

/** @var array<string, string> $columns label => text */
if (isset($options['file']) && is_string($options['file'])) {
    $path = $options['file'];
    $text = is_file($path) ? (string) file_get_contents($path) : '';
    if (trim($text) === '') {
        fwrite(STDERR, "Error: file not found or empty - {$path}\n");
        exit(1);
    }
    $columns = ['Your text' => $text];
} else {
    $columns = [
        'Plain text' => 'We made an app that reads your text. It tells you how easy it is to read. You get a score in one second.',
        'Legalese' => 'The parties acknowledge that any unauthorized disclosure of confidential information may cause irreparable harm. In such an event, the affected party shall be entitled to seek injunctive relief.',
    ];
}

/** @var array<string, TextStatistics> $stats */
$stats = [];
foreach ($columns as $label => $text) {
    $stats[$label] = $engine->analyze($text);
}

function pad(string $s, int $width, int $dir = STR_PAD_RIGHT): string
{
    $len = mb_strlen($s);
    if ($len >= $width) {
        return $s;
    }
    $fill = str_repeat(' ', $width - $len);

    return $dir === STR_PAD_LEFT ? $fill . $s : $s . $fill;
}

/**
 * @param list<string>       $header
 * @param list<list<string>> $rows
 */
function table(array $header, array $rows, int $valueAlign = STR_PAD_LEFT): void
{
    $widths = [];
    foreach ([$header, ...$rows] as $row) {
        foreach ($row as $i => $cell) {
            $widths[$i] = max($widths[$i] ?? 0, mb_strlen($cell));
        }
    }

    $border = '+';
    foreach ($widths as $w) {
        $border .= str_repeat('-', $w + 2) . '+';
    }

    $line = static function (array $row) use ($widths, $valueAlign): void {
        $out = '|';
        foreach ($row as $i => $cell) {
            $align = $i === 0 ? STR_PAD_RIGHT : $valueAlign;
            $out .= ' ' . pad($cell, $widths[$i], $align) . ' |';
        }
        echo $out . "\n";
    };

    echo $border . "\n";
    $line($header);
    echo $border . "\n";
    foreach ($rows as $row) {
        $line($row);
    }
    echo $border . "\n";
}

$labels = array_keys($columns);

echo "\n";
echo '  ╔' . str_repeat('═', 58) . "╗\n";
printf("  ║  ReadSight dashboard  ·  %-31s ║\n", $engine->getLanguage()->name . " ({$langCode})");
echo '  ╚' . str_repeat('═', 58) . "╝\n\n";

// --- Text metrics ---
$metrics = [
    'Letters' => static fn (TextStatistics $s): string => (string) $s->letterCount,
    'Words' => static fn (TextStatistics $s): string => (string) $s->wordCount,
    'Sentences' => static fn (TextStatistics $s): string => (string) $s->sentenceCount,
    'Syllables' => static fn (TextStatistics $s): string => (string) $s->syllableCount,
    'Polysyllabic words (3+)' => static fn (TextStatistics $s): string => (string) $s->polysyllableCount,
    'Long words (>6 letters)' => static fn (TextStatistics $s): string => (string) $s->longWordCount,
    'Avg syllables / word' => static fn (TextStatistics $s): string => number_format($s->averageSyllablesPerWord, 2),
    'Avg words / sentence' => static fn (TextStatistics $s): string => number_format($s->averageWordsPerSentence, 2),
];

$rows = [];
foreach ($metrics as $name => $fn) {
    $row = [$name];
    foreach ($labels as $label) {
        $row[] = $fn($stats[$label]);
    }
    $rows[] = $row;
}
table(['TEXT METRIC', ...$labels], $rows);

// --- Syllable histogram ---
$maxSyllable = 0;
foreach ($stats as $s) {
    $keys = array_keys($s->syllableHistogram);
    $maxSyllable = max($maxSyllable, $keys === [] ? 0 : max($keys));
}
if ($maxSyllable > 0) {
    echo "\nSyllable histogram (words grouped by syllable count):\n";
    foreach ($labels as $label) {
        $hist = $stats[$label]->syllableHistogram;
        printf("  %s\n", $label);
        for ($n = 1; $n <= $maxSyllable; $n++) {
            $count = $hist[$n] ?? 0;
            printf("    %d syllable %s %d\n", $n, str_repeat('█', $count), $count);
        }
    }
}

// --- Readability formulas ---
$formulaLabels = [
    'flesch_reading_ease' => 'Flesch Reading Ease',
    'flesch_kincaid_grade_level' => 'Flesch-Kincaid Grade',
    'gunning_fog' => 'Gunning Fog',
    'smog' => 'SMOG Index',
    'coleman_liau' => 'Coleman-Liau',
    'ari' => 'Automated Readability',
    'lix' => 'LIX',
    'wiener_sachtextformel' => 'Wiener Sachtextformel',
    'gulpease' => 'Gulpease',
    'fernandez_huerta' => 'Fernandez-Huerta',
    'szigriszt_pazos' => 'Szigriszt-Pazos',
    'gutierrez_polini' => 'Gutierrez-Polini',
    'crawford' => 'Crawford',
    'fog_pl' => 'FOG-PL',
    'dale_chall' => 'Dale-Chall',
    'spache' => 'Spache',
    'osman' => 'OSMAN',
];

$supported = $engine->getSupportedFormulas();
$formulaRows = [];
foreach ($formulaLabels as $key => $nice) {
    if (!in_array($key, $supported, true)) {
        continue;
    }
    $row = [$nice];
    foreach ($columns as $label => $text) {
        $result = $engine->score($key, $text);
        $grade = $result->gradeLevel !== null ? sprintf('g%.1f ', $result->gradeLevel) : '';
        $row[] = sprintf('%.1f  %s%s', $result->score, $grade, $result->interpretation);
    }
    $formulaRows[] = $row;
}

echo "\n";
table(['READABILITY FORMULA', ...$labels], $formulaRows, STR_PAD_RIGHT);
echo "\n";
