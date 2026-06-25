# ReadSight — Multilingual Readability Engine

PHP library for measuring text readability across **78 languages** using the Frank M. Liang (TeX) algorithm
and 17 readability formulas with language-specific coefficients.

## Installation

```bash
composer require globus-studio/readsight
```

Requires PHP >= 8.5.0 with `ext-mbstring` and `ext-json`.

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

## Supported Languages

78 languages across 16 writing systems: English, German, Russian, French, Spanish,
Italian, Dutch, Portuguese, Polish, Turkish, Ukrainian, Swedish, Finnish, Greek,
Armenian, Georgian, Hindi, Bengali, Tamil, Thai, Arabic, and many more.

```php
$engine = new Engine('ru');    // Russian
$engine = new Engine('de-1996'); // German
$engine = new Engine('es');    // Spanish
$engine = new Engine('th');    // Thai

// List all supported languages
$langs = Engine::getSupportedLanguages();
```

## Readability Formulas

### Universal (all 78 languages)

| Formula | Method | Type |
|---|---|---|
| Gunning Fog | `gunningFog()` | Syllable-based |
| SMOG Index | `smogIndex()` | Syllable-based |
| Coleman-Liau | `colemanLiau()` | Letter-based |
| ARI | `automatedReadabilityIndex()` | Letter-based |
| LIX | `lix()` | Letter-based (configurable threshold) |

### Language-Specific

| Language | Formulas |
|---|---|
| English (en-us, en-gb) | Flesch Reading Ease, FK Grade Level, Dale-Chall, Spache |
| German (de-*) | Flesch Reading Ease (Amstad), FKGL, Wiener Sachtextformel |
| Russian (ru) | Flesch Reading Ease (Oborneva), FKGL |
| Spanish (es) | Flesch Reading Ease, Fernandez-Huerta, Szigriszt-Pazos, Gutierrez-Polini, Crawford |
| Italian (it) | Flesch Reading Ease, Gulpease |
| French (fr) | Flesch Reading Ease (Kandel-Moles) |
| Dutch (nl) | Flesch Reading Ease (Douma) |
| Portuguese (pt) | Flesch Reading Ease (Martins) |
| Turkish (tr) | Flesch Reading Ease (Ateşman) |
| Polish (pl) | FOG-PL |
| Arabic (ar) | OSMAN |

Generic method for any formula:
```php
$result = $engine->score('gunning_fog', $text);
```

### FormulaResult

```php
$result->score;           // float — raw formula score
$result->gradeLevel;      // ?float — normalized grade level (FKGL, GF, SMOG, CL, ARI)
$result->gradeLabel;      // ?string — human-readable label ("6th Grade")
$result->interpretation;  // string — qualitative interpretation ("Easy", "Hard")
$result->inputs;          // array — intermediate values for debugging
```

## Performance

| Operation | Time |
|---|---|
| Syllable counting (single word) | ~0.15 ms |
| Text analysis (450 words) | ~20 ms |
| Formula calculation (incl. analysis) | ~4 ms |
| Engine init (en-us, cached) | ~5 ms |
| Engine init (de-1996, first load) | ~380 ms |

Caching: compiled patterns are cached as JSON files in `cache/` directory.
First load parses `.pat.txt`; subsequent loads use cache.

## Custom Configuration

```php
use GlobusStudio\ReadSight\Engine;

// Set default directories (before creating engines)
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
  ├── LiangHyphenator (TeX hyphenation algorithm)
  │   ├── PatternsCollection (from .pat.txt)
  │   ├── ExceptionsCollection (from .hyp.txt)
  │   └── JsonPatternCache (compiled patterns)
  ├── TextSplitter (word/sentence/letter counting)
  ├── Language (JSON config per language)
  └── FormulaRegistry (17 formulas)
      ├── FleschReadingEase (with lang-specific coefficients)
      ├── GunningFog, SMOG, ColemanLiau, ARI, LIX (universal)
      └── WSTF, Gulpease, Fernandez-Huerta, etc. (lang-specific)
```

## Data Sources

- **TeX hyphenation patterns**: [hyph-utf8](https://ctan.org/pkg/hyph-utf8) version 2026-02-21 —
  the canonical TeX hyphenation repository maintained by the TeX Users Group (TUG).
  98 files: 78 `.pat.txt` + 20 `.hyp.txt` covering 78 languages.
  Packaged under each pattern file's original license.
- **FRE coefficients**: Amstad (DE), Oborneva (RU), Fernandez-Huerta (ES),
  Vacca-Franchina (IT), Kandel-Moles (FR), Douma (NL), Martins (PT), Ateşman (TR)
- **WSTF**: Bamberger & Vanecek (DE)
- **Gulpease**: GULP, La Sapienza University (IT)

## Development

```bash
composer install
composer test          # PHPUnit
composer analyse       # PHPStan level max
composer check         # php-cs-fixer + PHPStan + PHPUnit
```

## License

MIT. Author: Yevhen Leonidov.

Pattern files from hyph-utf8 have their own licenses (see file headers).
