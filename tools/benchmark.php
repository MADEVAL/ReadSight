<?php

declare(strict_types=1);

/**
 * ReadSight Performance Benchmark
 *
 * Usage: php tools/benchmark.php
 */

require __DIR__ . '/../vendor/autoload.php';

use GlobusStudio\ReadSight\Engine;

$dataDir = __DIR__ . '/../data';
$iterations = 100;

function benchmark(string $label, callable $fn, int $iterations = 100): float
{
    $start = \hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $fn();
    }
    $elapsed = (\hrtime(true) - $start) / 1e9;

    echo \sprintf("  %-40s %8.2f ms/op  (%d iterations, %.2fs total)\n",
        $label,
        ($elapsed / $iterations) * 1000,
        $iterations,
        $elapsed,
    );

    return $elapsed;
}

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║        ReadSight Performance Benchmarks              ║\n";
echo "╠══════════════════════════════════════════════════════╣\n";
echo "║  PHP " . PHP_VERSION . "  |  hyph-utf8 2026-02-21                   ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

echo "--- Engine Initialization ---\n";

benchmark('Engine construct (en-us)', function () use ($dataDir) {
    new Engine('en-us', $dataDir . '/patterns', $dataDir . '/languages', \sys_get_temp_dir() . '/bench-cache');
}, 10);

$engine = new Engine('en-us', $dataDir . '/patterns', $dataDir . '/languages', \sys_get_temp_dir() . '/bench-cache');

echo "\n--- Syllable Counting ---\n";

$words = ['the', 'banana', 'computer', 'beautiful', 'extraordinary', 'communication'];
foreach ($words as $word) {
    benchmark("syllableCount('{$word}')", function () use ($engine, $word) {
        $engine->syllableCount($word);
    }, $iterations);
}

echo "\n--- Text Analysis ---\n";

$shortText = 'The quick brown fox jumps over the lazy dog.';
benchmark("analyze('{$shortText}')", function () use ($engine, $shortText) {
    $engine->analyze($shortText);
}, $iterations);

$longText = \str_repeat('The quick brown fox jumps over the lazy dog. ', 50);
$wordCount = \str_word_count($longText);
benchmark("analyze({$wordCount} words)", function () use ($engine, $longText) {
    $engine->analyze($longText);
}, 20);

echo "\n--- Readability Formulas ---\n";

$text = "Readability is the ease with which a reader can understand a written text. " .
    "Natural language processing tools help measure this important quality. " .
    "Various formulas have been developed over decades of research. " .
    "These include the Flesch-Kincaid tests and many others.";

benchmark("fleschReadingEase", function () use ($engine, $text) {
    $engine->fleschReadingEase($text);
}, $iterations);

benchmark("gunningFog", function () use ($engine, $text) {
    $engine->gunningFog($text);
}, $iterations);

benchmark("lix", function () use ($engine, $text) {
    $engine->lix($text);
}, $iterations);

benchmark("colemanLiau", function () use ($engine, $text) {
    $engine->colemanLiau($text);
}, $iterations);

echo "\n--- Multi-language Load ---\n";

$langs = ['en-us', 'ru', 'de-1996', 'fr', 'es', 'it', 'pt', 'nl', 'tr', 'uk'];

foreach ($langs as $lang) {
    benchmark("load '{$lang}'", function () use ($lang, $dataDir) {
        new Engine($lang, $dataDir . '/patterns', $dataDir . '/languages', \sys_get_temp_dir() . '/bench-cache');
    }, 3);
}

echo "\nDone.\n";
