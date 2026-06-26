# ReadSight - Multilingual Readability Engine

[![CI](https://github.com/MADEVAL/ReadSight/actions/workflows/ci.yml/badge.svg)](https://github.com/MADEVAL/ReadSight/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.2-777bb3?logo=php)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-257%20passed-brightgreen)](https://github.com/MADEVAL/ReadSight)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)](https://phpstan.org/)
[![Languages](https://img.shields.io/badge/languages-86-9cf)](https://github.com/MADEVAL/ReadSight)
[![Formulas](https://img.shields.io/badge/formulas-17-orange)](https://github.com/MADEVAL/ReadSight)

ReadSight is a PHP library for measuring text readability across **86 languages**. It implements **17 readability formulas** with language-specific coefficients and uses the Frank M. Liang (TeX) hyphenation algorithm for accurate syllable counting — all with **zero runtime dependencies**.

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Syllable Counting Modes](#syllable-counting-modes)
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
- PHP >= 8.2
- `ext-mbstring`
- `ext-json`

No other runtime dependencies.

## Quick Start

```php
use GlobusStudio\ReadSight\Engine;

$engine = new Engine('en-us');

// Syllable counting
$engine->syllableCount('banana');        // 3
$engine->splitSyllables('hyphenation');  // ['hyp', 'hen', 'ati', 'on']  (4 syllables, heuristic split)
$engine->splitWord('hyphenation');       // ['hy', 'phen', 'ation']      (TeX hyphenation points)

// Text analysis
$stats = $engine->analyze('The quick brown fox jumps over the lazy dog.');
echo "Words: {$stats->wordCount}, Syllables: {$stats->syllableCount}\n";

// Readability formulas
$fre = $engine->fleschReadingEase($text);
echo "Flesch Reading Ease: {$fre->score} - {$fre->interpretation}\n";

$fog = $engine->gunningFog($text);
echo "Gunning Fog: {$fog->score} (grade {$fog->gradeLevel})\n";

$lix = $engine->lix($text);
echo "LIX: {$lix->score} - {$lix->interpretation}\n";
```

## Syllable Counting Modes

ReadSight has three syllable counting modes, configured per language via `syllableMode` in `data/languages/*.json`:

| Mode | How it works | `count` accuracy | `split` accuracy |
|---|---|---|---|
| **`heuristic`** | Vowel patterns + word list + prefix/suffix rules | ✓ | ≈ approximate |
| **`tex`** | Frank M. Liang hyphenation algorithm (TeX `.tex` patterns) | ✓ | ✓ exact |
| **`composite`** | Heuristic first, TeX as fallback | ✓ | ≈ approximate (uses heuristic split) |

The default mode is **`tex`**. **84 languages use `tex`**; **2 use `composite`** (`en-us`, `en-gb`).

### Example: "hyphenation" in each mode

```php
$engine = new Engine('en-us');     // composite mode - heuristic wins
$engine->syllableCount('hyphenation');    // 4 ✓ (in problemWords list)
$engine->splitSyllables('hyphenation');   // ['hyp', 'hen', 'ati', 'on']  - heuristic: equal-width split, ≈ approximate
$engine->splitWord('hyphenation');        // ['hy', 'phen', 'ation']      - TeX hyphenator: exact points

$engine = new Engine('de-1996');   // tex mode
$engine->syllableCount('hyphenation');    // 4 ✓ (TeX patterns)
$engine->splitSyllables('hyphenation');   // ['hy', 'phena', 'ti', 'on']  - TeX: exact
$engine->splitWord('hyphenation');        // ['hy', 'phena', 'ti', 'on']  - same, both use TeX
```

> **Tip:** `splitWord()` always uses the TeX hyphenator (exact). `splitSyllables()` may use the heuristic split (approximate) in `composite`/`heuristic` modes. For syllable *counts* both are correct.

> **Note:** `addHyphenations()` adds overrides to the TeX hyphenator. These affect `splitWord()` but NOT `splitSyllables()` in `composite`/`heuristic` modes (the heuristic counter doesn't see them).

## Demo

Run the interactive demo to see ReadSight in action:

```bash
php examples/demo.php
```

This analyzes built-in sample text and outputs:
- **Syllable breakdown** with hyphenation points for common words
- **Text statistics** - letters, words, sentences, syllables, histogram
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

86 languages across **19 writing systems**: Latin, Cyrillic, Arabic, Hebrew, Devanagari, Bengali, Tamil, Thai, Greek, Armenian, Georgian, Gujarati, Gurmukhi, Kannada, Malayalam, Odia, Telugu, Ethiopic, Coptic.

```php
$engine = new Engine('ru');       // Russian
$engine = new Engine('de-1996');  // German (1996 reform)
$engine = new Engine('es');       // Spanish
$engine = new Engine('th');       // Thai

// List all supported languages
$langs = Engine::getSupportedLanguages();
# ['af', 'ar', 'as', 'be', 'bg', 'bn', 'ca', 'cop', 'cs', 'cu', 'cy', 'da',
#  'de-1901', 'de-1996', 'de-ch-1901', 'el-monoton', 'el-polyton', 'en-gb',
#  'en-us', 'eo', 'es', 'et', 'eu', 'fa', 'fi', 'fi-x-school', 'fr', 'fur',
#  'ga', 'gl', 'grc', 'gu', 'he', 'hi', 'hr', 'hsb', 'hu', 'hy', 'ia', 'id',
#  'is', 'it', 'ka', 'kk', 'kmr', 'kn', 'la', 'la-x-classic', 'la-x-liturgic',
#  'lt', 'lv', 'mk', 'ml', 'mn-cyrl', 'mn-cyrl-x-lmc', 'mr', 'mul-ethi', 'nb',
#  'nl', 'nn', 'oc', 'or', 'pa', 'pi', 'pl', 'pms', 'pt', 'rm', 'ro', 'ru',
#  'sa', 'sh-cyrl', 'sh-latn', 'sk', 'sl', 'sq', 'sr-cyrl', 'sv', 'ta', 'te',
#  'th', 'tk', 'tr', 'uk', 'vi', 'zh-latn-pinyin']
```

## Readability Formulas

### Universal (all 86 languages)

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
$result->score;           // float - raw formula score
$result->gradeLevel;      // ?float - normalized grade level (FKGL, GF, SMOG, CL, ARI)
$result->interpretation;  // string - qualitative interpretation ("Easy", "Hard")
$result->formulaName;     // string - formula key
$result->languageCode;    // string - language code used
$result->inputs;          // array<string, float|int> - intermediate values for debugging
```

### API Reference

#### Text Analysis Methods

```php
$engine->syllableCount(string $word): int
$engine->splitWord(string $word): list<string>
$engine->splitSyllables(string $word): list<string>
$engine->wordCount(string $text): int
$engine->sentenceCount(string $text): int
$engine->letterCount(string $text): int
$engine->totalSyllables(string $text): int
$engine->averageSyllablesPerWord(string $text): float
$engine->averageWordsPerSentence(string $text): float
$engine->polysyllableCount(string $text, bool $countProperNouns = true): int
$engine->wordsWithMoreThanNSyllables(string $text, int $n, bool $countProperNouns = true): int
$engine->histogramSyllables(string $text): array<int, int>
$engine->analyze(string $text): TextStatistics
```

> **`splitSyllables` vs `splitWord`:** `splitSyllables` may use the heuristic ≈approximate split (depends on the language's `syllableMode`). `splitWord` always uses the TeX hyphenator for exact hyphenation points. Syllable *counts* are accurate in all modes. See [Syllable Counting Modes](#syllable-counting-modes).

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
First load parses `.tex` files (native hyph-utf8 format); subsequent loads use the pre-compiled cache.

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

// Add custom hyphenation rules (affects splitWord, not splitSyllables in composite/heuristic modes)
$engine->addHyphenations([
    'customword' => 'cus-tom-word',
]);
$engine->splitWord('customword');  // ['cus', 'tom', 'word']
```

## Architecture

```
Engine (facade)
  ├── TextAnalyzer (syllable counting, text metrics)
  │   ├── SyllableCounter (strategy: tex | heuristic | composite)
  │   │   ├── CompositeSyllableCounter (problemWords → heuristic, rest → TeX)
  │   │   ├── HeuristicSyllableCounter (vowel patterns + word list)
  │   │   └── TexSyllableCounter → LiangHyphenator (TeX hyphenation)
  │   ├── LiangHyphenator
  │   │   ├── TexSource (parses .tex from hyph-utf8)
  │   │   ├── PatternsCollection (pattern data)
  │   │   ├── HyphenationExceptionsCollection (word overrides)
  │   │   └── JsonPatternCache (compiled patterns)
  │   └── TextSplitter (word/sentence/letter counting)
  ├── Language (JSON config per language, syllableMode + formulaConfigs)
  └── FormulaRegistry (17 formulas)
      ├── FleschReadingEase (with lang-specific coefficients)
      ├── GunningFog, SMOG, ColemanLiau, ARI, LIX (universal)
      └── WSTF, Gulpease, Fernandez-Huerta, etc. (lang-specific)
```

## Data Sources

- **TeX hyphenation patterns**: [hyph-utf8](https://ctan.org/pkg/hyph-utf8) version 2026-02-21 -
  the canonical TeX hyphenation repository maintained by the TeX Users Group (TUG).
   86 `.tex` pattern files from hyph-utf8 covering 86 language variants.
  Packaged under each pattern file's original license.
- **FRE coefficients**: Amstad (DE), Oborneva (RU), Fernandez-Huerta (ES),
  Vacca-Franchina (IT), Kandel-Moles (FR), Douma (NL), Martins (PT), Ateşman (TR)
- **WSTF**: Bamberger & Vanecek (DE)
- **Gulpease**: GULP, La Sapienza University (IT)

## Development

```bash
composer install          # Install dependencies

composer test             # Run PHPUnit (257 tests)
composer test:coverage    # With HTML coverage report
composer analyse          # PHPStan level max
composer cs:check         # PHP CS Fixer (dry-run)
composer cs:fix           # PHP CS Fixer (apply fixes)
composer check            # All checks: CS + PHPStan + Tests
```

### Quality Metrics

| Metric | Value |
|---|---|---|
| Tests | **257** |
| Assertions | **1 047** |
| PHPStan | **Level max, 0 errors** |
| Source classes | 53 |
| Test classes | 21 |
| Supported languages | 86 |
| Writing systems | 19 |
| Readability formulas | 17 |
| Runtime dependencies | **0** |

## License

MIT. Author: Yevhen Leonidov.

TeX pattern files from hyph-utf8 are packaged under their original licenses (see individual file headers).
