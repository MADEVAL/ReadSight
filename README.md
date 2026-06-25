# ReadSight — Multilingual Readability Engine

[![CI](https://github.com/MADEVAL/ReadSight/actions/workflows/ci.yml/badge.svg)](https://github.com/MADEVAL/ReadSight/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.5-777bb3?logo=php)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-176%20passed-brightgreen)](https://github.com/MADEVAL/ReadSight)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)](https://phpstan.org/)
[![Languages](https://img.shields.io/badge/languages-79-9cf)](https://github.com/MADEVAL/ReadSight)
[![Formulas](https://img.shields.io/badge/formulas-17-orange)](https://github.com/MADEVAL/ReadSight)

PHP library for measuring text readability across **79 languages** using the Frank M. Liang (TeX) hyphenation algorithm
and **17 readability formulas** with language-specific coefficients.

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Demo](#demo)
- [Supported Languages](#supported-languages)
- [Readability Formulas](#readability-formulas)
- [FormulaResult](#formularesult)
- [Performance](#performance)
- [Custom Configuration](#custom-configuration)
- [Architecture](#architecture)
- [Data Sources](#data-sources)
- [Development](#development)
- [License](#license)

## Installation

```bash
composer require globus-studio/readsight
```

**Requirements:**
- PHP >= 8.5.0
- `ext-mbstring`
- `ext-json`

No other runtime dependencies.

## Quick Start

```php
use GlobusStudio\ReadSight\Engine;

$engine = new Engine('en-us');

// Syllable counting
$engine->syllableCount('banana');        // 3
$engine->splitWord('hyphenation');       // ['hy', 'phen', 'ation']

// Text analysis
$stats = $engine->analyze('The quick brown fox jumps over the lazy dog.');
echo "Words: {$stats->wordCount}, Syllables: {$stats->syllableCount}\n";

// Readability formulas
$fre = $engine->fleschReadingEase($text);
echo "Flesch Reading Ease: {$fre->score} — {$fre->gradeLabel}\n";

$fog = $engine->gunningFog($text);
echo "Gunning Fog: {$fog->score} (grade {$fog->gradeLevel})\n";

$lix = $engine->lix($text);
echo "LIX: {$lix->score} — {$lix->interpretation}\n";
```

## Demo

Run the interactive demo to see ReadSight in action:

```bash
php examples/demo.php
```

This analyzes built-in sample text and outputs:
- **Syllable breakdown** with hyphenation points for common words
- **Text statistics** — letters, words, sentences, syllables, histogram
- **All applicable readability formulas** with scores and interpretations

Compare the same text across 8 languages:

```bash
php examples/demo.php --compare
```

Analyze your own text file:

```bash
php examples/demo.php --file=essay.txt
php examples/demo.php --file=essay.txt --lang=de-1996
```

## Supported Languages

79 languages across **16 writing systems**: Latin, Cyrillic, Arabic, Devanagari, Bengali, Tamil, Thai, Greek, Armenian, Georgian, Gujarati, Gurmukhi, Kannada, Malayalam, Odia, Telugu, Ethiopic, Coptic, and more.

```php
$engine = new Engine('ru');       // Russian
$engine = new Engine('de-1996');  // German (1996 reform)
$engine = new Engine('es');       // Spanish
$engine = new Engine('th');       // Thai

// List all supported languages
$langs = Engine::getSupportedLanguages();
// ['af', 'ar', 'as', 'be', 'bg', 'bn', 'ca', 'cop', 'cs', 'cy', 'da', 'de-1901', 'de-1996', 'de-ch-1901', 'el-monoton', 'el-polyton', 'en-gb', 'en-us', 'eo', 'es', 'et', 'eu', 'fi', 'fr', 'ga', 'gl', 'gu', 'hi', 'hr', 'hsb', 'hu', 'hy', 'ia', 'id', 'is', 'it', 'ka', 'kmr', 'kn', 'la', 'lt', 'lv', 'ml', 'mn-cyrl', 'mr', 'mul-ethi', 'nb', 'nl', 'nn', 'or', 'pa', 'pl', 'pt', 'rm', 'ro', 'ru', 'sa', 'sh-cyrl', 'sk', 'sl', 'sr-cyrl', 'sv', 'ta', 'te', 'th', 'tk', 'tr', 'uk', 'zh-latn-pinyin']
```

## Readability Formulas

### Universal (all 79 languages)

| Formula | Method | Type | Score Range |
|---|---|---|---|
| Gunning Fog | `gunningFog()` | Syllable-based | 0–20+ |
| SMOG Index | `smogIndex()` | Syllable-based | 3–18+ |
| Coleman-Liau | `colemanLiau()` | Letter-based | 0–18+ |
| ARI | `automatedReadabilityIndex()` | Letter-based | 0–18+ |
| LIX | `lix()` | Letter-based | 20–60+ |

### Language-Specific

| Language | Formulas |
|---|---|
| English (`en-us`, `en-gb`) | Flesch Reading Ease, FK Grade Level, Dale-Chall*, Spache* |
| German (`de-*`) | Flesch Reading Ease (Amstad), FKGL, Wiener Sachtextformel (4 variants) |
| Russian (`ru`) | Flesch Reading Ease (Oborneva), FKGL |
| Spanish (`es`) | Flesch Reading Ease, Fernandez-Huerta, Szigriszt-Pazos, Gutierrez-Polini, Crawford |
| Italian (`it`) | Flesch Reading Ease, Gulpease |
| French (`fr`) | Flesch Reading Ease (Kandel-Moles) |
| Dutch (`nl`) | Flesch Reading Ease (Douma) |
| Portuguese (`pt`) | Flesch Reading Ease (Martins) |
| Turkish (`tr`) | Flesch Reading Ease (Ateşman) |
| Polish (`pl`) | FOG-PL |
| Arabic (`ar`) | OSMAN |

> \* **Note:** Dale-Chall and Spache formulas use a syllable-based heuristic to estimate difficult words (1-syllable ≈ easy). This is a simplified estimation, not based on the original Dale/Spache word lists. For accurate Dale-Chall/Spache scores, a curated word list would be required.

Generic dispatching:

```php
$result = $engine->score('gunning_fog', $text);
$result = $engine->score('wiener_sachtextformel', $text);
```

## FormulaResult

```php
$result->score;           // float — raw formula score
$result->gradeLevel;      // ?float — normalized grade level (FKGL, GF, SMOG, CL, ARI)
$result->gradeLabel;      // ?string — human-readable label ("6th Grade")
$result->interpretation;  // string — qualitative interpretation ("Easy", "Hard")
$result->formulaName;     // string — formula key
$result->languageCode;    // string — language code used
$result->inputs;          // array — intermediate values for debugging
```

### API Reference

#### Text Analysis Methods

```php
$engine->syllableCount(string $word): int
$engine->splitWord(string $word): list<string>
$engine->wordCount(string $text): int
$engine->sentenceCount(string $text): int
$engine->letterCount(string $text): int
$engine->totalSyllables(string $text): int
$engine->averageSyllablesPerWord(string $text): float
$engine->averageWordsPerSentence(string $text): float
$engine->polysyllableCount(string $text, bool $countProperNouns = true): int
$engine->wordsWithNSyllables(string $text, int $n, bool $countProperNouns = true): int
$engine->histogramSyllables(string $text): array<int, int>
$engine->analyze(string $text): TextStatistics
```

#### Formula Methods

```php
$engine->fleschReadingEase(string $text): FormulaResult
$engine->fleschKincaidGradeLevel(string $text): FormulaResult
$engine->gunningFog(string $text): FormulaResult
$engine->smogIndex(string $text): FormulaResult
$engine->colemanLiau(string $text): FormulaResult
$engine->automatedReadabilityIndex(string $text): FormulaResult
$engine->lix(string $text): FormulaResult
$engine->wienerSachtextformel(string $text, int $variant = 1): FormulaResult
$engine->gulpease(string $text): FormulaResult
$engine->fernandezHuerta(string $text): FormulaResult
$engine->szigrisztPazos(string $text): FormulaResult
$engine->gutierrezPolini(string $text): FormulaResult
$engine->crawford(string $text): FormulaResult
$engine->fogPL(string $text): FormulaResult
$engine->daleChall(string $text): FormulaResult
$engine->spache(string $text): FormulaResult
$engine->osman(string $text): FormulaResult
```

## Performance

| Operation | Time |
|---|---|
| Syllable counting (single word) | ~0.15 ms |
| Text analysis (450 words) | ~20 ms |
| Formula calculation (incl. analysis) | ~4 ms |
| Engine init (en-us, cached) | ~5 ms |
| Engine init (de-1996, first load) | ~380 ms |

Caching: compiled patterns are stored as JSON in the `cache/` directory.
First load parses `.pat.txt` files; subsequent loads use the pre-compiled cache.

## Custom Configuration

```php
use GlobusStudio\ReadSight\Engine;

// Set default paths (before creating engines)
Engine::setDefaultCacheDir('/var/cache/readsight');
Engine::setDefaultPatternsDir('/usr/share/readsight/patterns');
Engine::setDefaultLanguagesDir('/usr/share/readsight/languages');

// Or per-instance
$engine = new Engine(
    language: 'en-us',
    patternsDir: '/custom/patterns',
    cacheDir: '/custom/cache',
);

// Add custom hyphenation rules
$engine->addHyphenations([
    'customword' => 'cus-tom-word',
]);
```

## Architecture

```
Engine (facade)
  ├── TextAnalyzer (syllable counting, text metrics)
  │   ├── LiangHyphenator (TeX hyphenation algorithm)
  │   │   ├── PatternsCollection (from .pat.txt)
  │   │   ├── ExceptionsCollection (from .hyp.txt)
  │   │   └── JsonPatternCache (compiled patterns)
  │   └── TextSplitter (word/sentence/letter counting)
  ├── Language (JSON config per language)
  └── FormulaRegistry (17 formulas)
      ├── FleschReadingEase (with lang-specific coefficients)
      ├── GunningFog, SMOG, ColemanLiau, ARI, LIX (universal)
      └── WSTF, Gulpease, Fernandez-Huerta, etc. (lang-specific)
```

## Data Sources

- **TeX hyphenation patterns**: [hyph-utf8](https://ctan.org/pkg/hyph-utf8) version 2026-02-21 —
  the canonical TeX hyphenation repository maintained by the TeX Users Group (TUG).
  99 files: 79 `.pat.txt` + 20 `.hyp.txt` covering 79 languages.
  Packaged under each pattern file's original license.
- **FRE coefficients**: Amstad (DE), Oborneva (RU), Fernandez-Huerta (ES),
  Vacca-Franchina (IT), Kandel-Moles (FR), Douma (NL), Martins (PT), Ateşman (TR)
- **WSTF**: Bamberger & Vanecek (DE)
- **Gulpease**: GULP, La Sapienza University (IT)

## Development

```bash
composer install          # Install dependencies

composer test             # Run PHPUnit (167 tests)
composer test:coverage    # With HTML coverage report
composer analyse          # PHPStan level max
composer cs:check         # PHP CS Fixer (dry-run)
composer cs:fix           # PHP CS Fixer (apply fixes)
composer check            # All checks: CS + PHPStan + Tests
```

### Quality Metrics

| Metric | Value |
|---|---|
| Tests | **175** |
| Assertions | **451** |
| PHPStan | **Level max, 0 errors** |
| PHP | 8.5.4 |
| Source classes | 40 |
| Test classes | 16 |
| Supported languages | 79 |
| Writing systems | 16 |
| Readability formulas | 17 |
| Runtime dependencies | **0** |

## License

MIT. Author: Yevhen Leonidov.

TeX pattern files from hyph-utf8 are packaged under their original licenses (see individual file headers).
